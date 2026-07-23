<?php
// استدعاء ملف الهيدر المشترك (يحتوي على config والاتصال بالقاعدة وحماية الجلسة)
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: التأكد من أن المستخدم مدرس
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$instructor_user_id = intval($_SESSION['user_id']);
$full_name = htmlspecialchars($_SESSION['full_name']);

// جلب الـ instructor_id الفعلي المرتبط بـ user_id من جدول المعلمين
$inst_query = "SELECT instructor_id FROM instructors WHERE user_id = ?";
$stmt_inst = $conn->prepare($inst_query);
$stmt_inst->bind_param("i", $instructor_user_id);
$stmt_inst->execute();
$inst_res = $stmt_inst->get_result()->fetch_assoc();
$instructor_id = $inst_res['instructor_id'] ?? 0;
$stmt_inst->close();
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-10 col-lg-8">
        <div class="card card-custom p-5 bg-white shadow-sm border-0" style="border-radius: 20px;">
            
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="fa-solid fa-sliders text-primary fa-3x"></i>
                </div>
                <h3 class="fw-bold text-dark">إنشاء exam جديد وبناء الإعدادات</h3>
                <p class="text-muted small">مرحباً دكتور: <span class="fw-bold text-primary"><?php echo $full_name; ?></span> - يتم الآن جلب مقرراتك ديناميكياً</p>
            </div>

            <form action="process_create_exam.php" method="POST">
                
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary"><i class="fa-solid fa-book me-2"></i>المقرر الدراسي (المادة)</label>
                    <select name="course_id" class="form-select form-select-lg rounded-3 fs-6" required>
                        <option value="" selected disabled>-- اختر المادة التابع لها الامتحان --</option>
                        <?php
                        // استعلام جلب المواد المرتبطة بهذا المدرس حصراً من جدول المخرجات أو جدول الامتحانات المعتمد لديك
                        $courses_query = "SELECT course_id, course_name, course_code FROM courses";
                        // ملاحظة: إذا كان لديك جدول ربط للمواد المسندة للمدرس يمكنك تفعيل الشرط أدناه:
                        // $courses_query .= " WHERE instructor_id = ? ";
                        
                        $result_courses = $conn->query($courses_query);
                        if ($result_courses && $result_courses->num_rows > 0) {
                            while ($course = $result_courses->fetch_assoc()) {
                                echo '<option value="'.intval($course['course_id']).'">'.htmlspecialchars($course['course_name']).' ('.htmlspecialchars($course['course_code']).')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary"><i class="fa-solid fa-heading me-2"></i>عنوان الامتحان</label>
                    <input type="text" name="title" class="form-control form-control-lg rounded-3 fs-6" placeholder="مثال: الامتحان النهائي - نظري" required>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label class="form-label fw-semibold text-secondary"><i class="fa-solid fa-calendar-day me-2"></i>موعد وتاريخ الامتحان الفعلي</label>
                        <input type="datetime-local" name="exam_date" class="form-control form-control-lg rounded-3 fs-6" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold text-secondary"><i class="fa-regular fa-clock me-2"></i>المدة الزمنية للامتحان</label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="duration" class="form-control rounded-start-3 fs-6" placeholder="مثال: 60" min="1" required>
                            <span class="input-group-text rounded-end-3 bg-light text-muted fs-6">دقيقة</span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-semibold text-secondary"><i class="fa-solid fa-graduation-cap me-2"></i>الدرجة الكلية للاختبار</label>
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control rounded-start-3 fs-6 bg-light text-center fw-bold text-primary" value="تُحسب تلقائياً" disabled>
                            <span class="input-group-text rounded-end-3 bg-light text-muted fs-6">درجة</span>
                        </div>
                        <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info text-info me-1"></i> سيقوم النظام بجمع درجات الأسئلة المعتمدة تلقائياً.</small>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill py-3" style="background-color: #003366; border: none;">
                        <i class="fa-solid fa-database me-2"></i> حفظ الامتحان والانتقال لربط بنك الأسئلة
                    </button>
                </div>

            </form>
            
        </div>
    </div>
</div>

<?php
// استدعاء الفوتر المشترك لغلق وسوم الـ HTML
include_once __DIR__ . '/../includes/footer.php';
?>