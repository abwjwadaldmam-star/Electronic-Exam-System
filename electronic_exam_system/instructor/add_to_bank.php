<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/auth_check.php';

// التحقق من أن المستخدم مدرس
checkAccess(['admin', 'instructor']);

$instructor_user_id = $_SESSION['user_id'] ?? 0;

// جلب الـ instructor_id الفعلي من جدول المدرسين بربطه بالجلسة
$ins_stmt = $conn->prepare("SELECT instructor_id, department FROM instructors WHERE user_id = ?");
$ins_stmt->bind_param("i", $instructor_user_id);
$ins_stmt->execute();
$ins_data = $ins_stmt->get_result()->fetch_assoc();
$instructor_id = $ins_data['instructor_id'] ?? 0;
$department = $ins_data['department'] ?? '';
$ins_stmt->close();

// معالجة إرسال النموذج وإدخال السؤال للبنك
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $course_id = intval($_POST['course_id']);
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'];
    $difficulty = $_POST['difficulty'];
    
    // إدخال السؤال بحالة pending (معلق) بانتظار رئيس القسم
    $insert_sql = "INSERT INTO question_bank (course_id, instructor_id, question_text, question_type, difficulty, status) VALUES (?, ?, ?, ?, ?, 'pending')";
    $add_stmt = $conn->prepare($insert_sql);
    if ($add_stmt) {
        $add_stmt->bind_param("iisss", $course_id, $instructor_id, $question_text, $question_type, $difficulty);
        $add_stmt->execute();
        $add_stmt->close();
        echo "<script>alert('تم إرسال السؤال بنجاح إلى بنك الأسئلة وهو بانتظار اعتماد رئيس القسم حالياً!');</script>";
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" dir="rtl">
    <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
        <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus-minus text-success me-2"></i> إضافة سؤال جديد إلى بنك الأسئلة المركزي</h4>
        <p class="text-muted small">ملاحظة: الأسئلة المضافة لن تدخل في الامتحانات الحية إلا بعد مراجعتها واعتمادها من رئيس القسم الأكاديمي.</p>
        <hr>
        
        <form action="" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-secondary">المقرر الدراسي:</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- اختر المادة --</option>
                        <?php
                        // جلب مواد القسم الخاص بالمدرس
                        $courses_res = $conn->query("SELECT course_id, course_name FROM courses WHERE department = '$department'");
                        while ($c_row = $courses_res->fetch_assoc()) {
                            echo "<option value='{$c_row['course_id']}'>".htmlspecialchars($c_row['course_name'])."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-secondary">درجة الصعوبة:</label>
                    <select name="difficulty" class="form-select">
                        <option value="easy">سهل</option>
                        <option value="medium" selected>متوسط</option>
                        <option value="hard">صعب</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-secondary">نوع السؤال:</label>
                    <select name="question_type" class="form-select">
                        <option value="mcq">اختيار من متعدد (MCQ)</option>
                        <option value="true_false">صح / خطأ</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold small text-secondary">نص السؤال الأكاديمي:</label>
                    <textarea name="question_text" class="form-select" rows="4" placeholder="اكتب هنا نص السؤال بدقة..." required></textarea>
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" name="submit_question" class="btn btn-success fw-bold px-4"><i class="fa-solid fa-cloud-arrow-up me-1"></i> رفع السؤال للبنك بانتظار الاعتماد</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>