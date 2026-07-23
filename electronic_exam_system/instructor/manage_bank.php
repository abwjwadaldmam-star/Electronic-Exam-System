<?php
// استدعاء ملف الهيدر وحماية الجلسة
include_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب الـ instructor_id الحقيقي
$instructor_id = 1; 
$inst_sql = "SELECT instructor_id FROM instructors WHERE user_id = '$user_id'";
$inst_res = $conn->query($inst_sql);
if ($inst_res && $inst_res->num_rows > 0) {
    $inst_row = $inst_res->fetch_assoc();
    $instructor_id = $inst_row['instructor_id'];
}

// جلب المقررات المسندة للمدرس لعرضها في القائمة
$courses_sql = "SELECT course_id, course_name, course_code FROM courses WHERE instructor_id = '$instructor_id'";
$courses_res = $conn->query($courses_sql);

// جلب الأسئلة الحالية المضافة للبنك بواسطة هذا المدرس
$questions_sql = "
    SELECT q.*, c.course_name 
    FROM question_bank q
    INNER JOIN courses c ON q.course_id = c.course_id
    WHERE c.instructor_id = '$instructor_id'
    ORDER BY q.question_id DESC
";
$questions_res = $conn->query($questions_sql);
?>

<div class="container-fluid px-4 mt-4">
    <div class="row g-4">
        
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-dark mb-4 text-center">
                    <i class="fa-solid fa-square-plus text-success me-2"></i> إضافة سؤال جديد للبنك
                </h5>
                
                <form action="process_add_to_bank.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">اختر المقرر الدراسي:</label>
                        <select name="course_id" class="form-select rounded-3" required>
                            <option value="">-- اختر المادة --</option>
                            <?php if ($courses_res && $courses_res->num_rows > 0): ?>
                                <?php while($course = $courses_res->fetch_assoc()): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name']) . " (" . $course['course_code'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نص السؤال الأكاديمي:</label>
                        <textarea name="question_text" class="form-control rounded-3" rows="4" placeholder="اكتب نص السؤال هنا..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-secondary">نوع السؤال:</label>
                            <select name="question_type" id="question_type" class="form-select rounded-3" required onchange="toggleQuestionFields()">
                                <option value="essay">مقالي (Text)</option>
                                <option value="mcq">اختيار من متعدد (MCQ)</option>
                                <option value="truefalse">صح وخطأ (True/False)</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-secondary">درجة السؤال:</label>
                            <input type="number" name="score" class="form-control rounded-3" min="1" value="1" required>
                        </div>
                    </div>

                    <div id="mcq_fields" class="p-3 bg-light rounded-3 mb-3" style="display: none;">
                        <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-list-ol me-1"></i> خيارات السؤال (الـ MCQ):</h6>
                        <div class="mb-2">
                            <input type="text" name="choice_1" class="form-control form-control-sm mb-1" placeholder="الخيار الأول (أ)">
                            <input type="text" name="choice_2" class="form-control form-control-sm mb-1" placeholder="الخيار الثاني (ب)">
                            <input type="text" name="choice_3" class="form-control form-control-sm mb-1" placeholder="الخيار الثالث (ج)">
                            <input type="text" name="choice_4" class="form-control form-control-sm mb-2" placeholder="الخيار الرابع (د)">
                        </div>
                        <label class="form-label small fw-bold text-success">تحديد الإجابة الصحيحة:</label>
                        <select name="correct_choice" class="form-select form-select-sm">
                            <option value="1">الخيار الأول (أ)</option>
                            <option value="2">الخيار الثاني (ب)</option>
                            <option value="3">الخيار الثالث (ج)</option>
                            <option value="4">الخيار الرابع (د)</option>
                        </select>
                    </div>

                    <div id="tf_fields" class="p-3 bg-light rounded-3 mb-3" style="display: none;">
                        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-check me-1"></i> تحديد الإجابة الصحيحة:</h6>
                        <select name="correct_tf" class="form-select form-select-sm">
                            <option value="true">صح (True)</option>
                            <option value="false">خطأ (False)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100 btn-lg shadow rounded-pill fw-bold fs-6 py-2 mt-2">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> رفع وحفظ في بنك الأسئلة
                    </button>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4" style="min-height: 530px;">
                <h5 class="fw-bold text-dark mb-4 text-center">
                    <i class="fa-solid fa-database text-warning me-2"></i> الأسئلة الحالية وحالة الاعتماد
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>المقرر</th>
                                <th class="text-start">نص السؤال</th>
                                <th>النوع</th>
                                <th>الدرجة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($questions_res && $questions_res->num_rows > 0): ?>
                                <?php while($q = $questions_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1"><?php echo htmlspecialchars($q['course_name']); ?></span></td>
                                        <td class="text-start fw-semibold text-dark"><?php echo htmlspecialchars($q['question_text']); ?></td>
                                        <td>
                                            <?php 
                                                // معالجة ذكية وشاملة لعرض نوع السؤال مهما كانت قيمته في القاعدة
                                                $type = trim(strtolower($q['question_type']));
                                                if($type == 'essay' || $type == 'text') {
                                                    echo '<span class="badge bg-secondary">مقالي</span>';
                                                } elseif($type == 'mcq' || $type == 'choices') {
                                                    echo '<span class="badge bg-info text-dark">خيارات</span>';
                                                } elseif($type == 'true_false' || $type == 'truefalse' || $type == 'tf' || empty($type)) {
                                                    // إذا كانت القيمة فارغة أو تحتوي على تسميات الصح والخطأ يظهر الخيار الافتراضي أصفر
                                                    echo '<span class="badge bg-warning text-dark">صح/خطأ</span>';
                                                } else {
                                                    // حقل احتياطي لعرض النص المخزن مباشرة في حال وجود قيمة أخرى
                                                    echo '<span class="badge bg-dark">' . htmlspecialchars($q['question_type']) . '</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="fw-bold text-danger"><?php echo $q['marks']; ?> د</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-5 text-center">
                                        <i class="fa-solid fa-folder-open fa-2x d-block mb-2 text-light-subtle"></i>
                                        لا توجد أسئلة مرفوعة في البنك الخاص بك حالياً.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function toggleQuestionFields() {
    var type = document.getElementById("question_type").value;
    var mcqFields = document.getElementById("mcq_fields");
    var tfFields = document.getElementById("tf_fields");

    if (type === "mcq") {
        mcqFields.style.display = "block";
        tfFields.style.display = "none";
        setRequiredForMCQ(true);
    } else if (type === "truefalse") {
        mcqFields.style.display = "none";
        tfFields.style.display = "block";
        setRequiredForMCQ(false);
    } else {
        mcqFields.style.display = "none";
        tfFields.style.display = "none";
        setRequiredForMCQ(false);
    }
}

function setRequiredForMCQ(isRequired) {
    if (document.getElementsByName("choice_1")[0]) {
        document.getElementsByName("choice_1")[0].required = isRequired;
        document.getElementsByName("choice_2")[0].required = isRequired;
        document.getElementsByName("choice_3")[0].required = isRequired;
        document.getElementsByName("choice_4")[0].required = isRequired;
    }
}
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>