<?php
// استدعاء ملف الهيدر المشترك (يحتوي على config والاتصال وحماية الجلسة)
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: التأكد من أن المستخدم مدرس ومسجل دخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// استقبال معرف الامتحان والتأكد من صحته
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id === 0) {
    echo '<div class="alert alert-danger mt-4 text-center">❌ خطأ: لم يتم تحديد إعدادات امتحان صالحة.</div>';
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}

// 1. جلب تفاصيل الامتحان والمادة المرتبطة به بأمان
$exam_query = "
    SELECT e.title, e.course_id, c.course_name, c.course_code 
    FROM exams e
    INNER JOIN courses c ON e.course_id = c.course_id
    WHERE e.exam_id = ? LIMIT 1
";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam_data) {
    echo '<div class="alert alert-danger mt-4 text-center">❌ خطأ: الامتحان غير موجود في النظام.</div>';
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}

$course_id = intval($exam_data['course_id']);
?>

<div class="row mt-4">
    <div class="col-12 mb-4">
        <div class="card p-4 text-white" style="background: linear-gradient(135deg, #003366 0%, #002244 100%); border-right: 5px solid #D4AF37; border-radius: 15px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <span class="badge bg-warning text-dark mb-2 px-3 py-1 rounded-pill">تهيئة الأسئلة من البنك المعتمد</span>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($exam_data['title']); ?></h3>
                    <p class="mb-0 opacity-75">المقرر: <?php echo htmlspecialchars($exam_data['course_name']); ?> (<?php echo htmlspecialchars($exam_data['course_code']); ?>)</p>
                </div>
                <div class="text-md-end mt-2 mt-md-0">
                    <div class="bg-white bg-opacity-10 p-3 rounded text-center">
                        <span class="d-block small opacity-75">إجمالي درجة الامتحان حالياً</span>
                        <h2 class="fw-bold mb-0 text-warning" id="live-total-marks">0 <span class="fs-6 text-white">درجة</span></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card p-4 bg-white shadow-sm border-0 h-100" style="border-radius: 15px;">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>التوليد والسحب العشوائي</h5>
            <p class="text-muted small">يمكنك جعل النظام يسحب عدداً محدداً من الأسئلة المعتمدة عشوائياً لهذه المادة وتضمينها فوراً.</p>
            <hr>
            
            <form id="random-selection-form" action="process_random_questions.php" method="POST">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">عدد الأسئلة المطلوب سحبها:</label>
                    <div class="input-group">
                        <input type="number" name="questions_count" class="form-control rounded-start" min="1" placeholder="مثال: 10" required>
                        <span class="input-group-text bg-light text-muted">سؤال</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill py-2">
                    <i class="fa-solid fa-shuffle me-2"></i> سحب واعتماد تلقائي
                </button>
            </form>
            
            <div class="mt-4 p-3 bg-light rounded border border-warning border-opacity-25">
                <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i> تنويه فني:</h6>
                <small class="text-muted d-block">عند استخدام السحب العشوائي، سيقوم النظام بالتحقق التلقائي لتفادي تكرار أي سؤال مضاف مسبقاً.</small>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card p-4 bg-white shadow-sm border-0 h-100" style="border-radius: 15px;">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-database text-warning me-2"></i>الأسئلة المتاحة في البنك المعتمد</h5>
            <p class="text-muted small">اضغط على زر "إضافة" لتضمين السؤال في الامتحان، أو "استبعاد" لإزالته ديناميكياً.</p>
            <hr>

            <?php
            // جلب كافة الأسئلة المعتمدة لهذ المقرر من جدول بنك الأسئلة
            $bank_query = "
                SELECT qb.question_id, qb.question_text, qb.question_type, qb.marks, u.full_name as author
                FROM question_bank qb
                INNER JOIN users u ON qb.instructor_id = u.user_id
                WHERE qb.course_id = ? AND qb.status = 'approved'
                ORDER BY qb.question_id DESC
            ";
            $stmt_b = $conn->prepare($bank_query);
            $stmt_b->bind_param("i", $course_id);
            $stmt_b->execute();
            $bank_result = $stmt_b->get_result();

            if ($bank_result && $bank_result->num_rows > 0):
            ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>نص السؤال</th>
                                <th>النوع</th>
                                <th>الدرجة</th>
                                <th class="text-center">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $bank_result->fetch_assoc()): 
                                $q_id = intval($row['question_id']);
                                
                                // التحقق ما إذا كان السؤال مضافاً مسبقاً لهذا الامتحان
                                $check_link = "SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ? LIMIT 1";
                                $stmt_c = $conn->prepare($check_link);
                                $stmt_c->bind_param("ii", $exam_id, $q_id);
                                $stmt_c->execute();
                                $is_linked = $stmt_c->get_result()->num_rows > 0;
                                $stmt_c->close();
                            ?>
                                <tr id="row-<?php echo $q_id; ?>">
                                    <td>
                                        <span class="fw-semibold text-dark d-block text-wrap" style="max-width: 350px;"><?php echo htmlspecialchars($row['question_text']); ?></span>
                                        <small class="text-muted fs-7">بواسطة: <?php echo htmlspecialchars($row['author']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            <?php echo ($row['question_type'] == 'mcq') ? 'اختيار من متعدد' : 'مقالي'; ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1"><?php echo intval($row['marks']); ?> د</span></td>
                                    <td class="text-center">
                                        <?php if ($is_linked): ?>
                                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 toggle-question-btn" data-qid="<?php echo $q_id; ?>" data-action="remove">استبعاد</button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 toggle-question-btn" data-qid="<?php echo $q_id; ?>" data-action="add">إضافة</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-folder-open text-muted fa-3x mb-3"></i>
                    <p class="text-muted fs-5">لا توجد أسئلة معتمدة متوفرة لهذا المقرر في البنك حالياً.</p>
                </div>
            <?php endif; $stmt_b->close(); ?>
        </div>
    </div>
    
    <div class="col-12 text-center mt-3 mb-5">
        <a href="dashboard.php" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill shadow" style="background-color: #003366; border:none;">
            <i class="fa-solid fa-circle-check me-2"></i> إنهاء وبناء الجلسة الامتحانية بنجاح
        </a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // دالة لتحديث المجموع الإجمالي للدرجات حياً في الواجهة
    function updateLiveTotalMarks() {
        fetch(`get_live_exam_marks.php?exam_id=<?php echo $exam_id; ?>`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById("live-total-marks").innerHTML = data.total + ' <span class="fs-6 text-white">درجة</span>';
                }
            });
    }

    // استدعاء فوري عند فتح الصفحة لحساب التوقيت الحالي للدرجات
    updateLiveTotalMarks();

    // معالجة النقر على أزرار الإضافة والاستبعاد الفورية
    document.querySelectorAll(".toggle-question-btn").forEach(button => {
        button.addEventListener("click", function() {
            const btn = this;
            const qid = btn.getAttribute("data-qid");
            const currentAction = btn.getAttribute("data-action");
            
            btn.disabled = true;
            btn.innerText = "جاري...";

            // إرسال طلب المعالجة الخلفي
            fetch('ajax_toggle_exam_question.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `exam_id=<?php echo $exam_id; ?>&question_id=${qid}&action=${currentAction}`
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if(data.success) {
                    if(currentAction === 'add') {
                        btn.setAttribute("data-action", "remove");
                        btn.className = "btn btn-danger btn-sm rounded-pill px-3 toggle-question-btn";
                        btn.innerText = "استبعاد";
                    } else {
                        btn.setAttribute("data-action", "add");
                        btn.className = "btn btn-outline-primary btn-sm rounded-pill px-3 toggle-question-btn";
                        btn.innerText = "إضافة";
                    }
                    // تحديث المجموع الحي
                    updateLiveTotalMarks();
                } else {
                    alert("❌ خطأ: " + data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                alert("❌ حدث خطأ في الاتصال بالخادم.");
            });
        });
    });
});
</script>

<?php
// استدعاء الفوتر المشترك لغلق وسوم الـ HTML وحفظ الحقوق
include_once __DIR__ . '/../includes/footer.php';
?>