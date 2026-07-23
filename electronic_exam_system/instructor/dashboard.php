<?php
// استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: السماح للمدرسين فقط بدخول هذا القسم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// جلب معرف المستخدم الحالي من الجلسة (user_id = 2 بناءً على جدولك)
$user_id = $_SESSION['user_id']; 

// 1. جلب الـ instructor_id الحقيقي المقابل للمستخدم الحالي من جدول المدرسين لضمان ربط المقررات بشكل سليم
$instructor_id = 1; // قيمة احتياطية افتراضية
$inst_sql = "SELECT instructor_id FROM instructors WHERE user_id = '$user_id'";
$inst_res = $conn->query($inst_sql);
if ($inst_res && $inst_res->num_rows > 0) {
    $inst_row = $inst_res->fetch_assoc();
    $instructor_id = $inst_row['instructor_id']; // سيجلب القيمة 1 ديناميكياً للدكتور محمد
}

// 2. جلب إحصائية إجمالي الامتحانات الخاصة بهذا المدرس فقط وبناءً على مقرراته
$total_exams = 0;
$count_exams_sql = "
    SELECT COUNT(*) as total FROM exams e 
    INNER JOIN courses c ON e.course_id = c.course_id 
    WHERE c.instructor_id = '$instructor_id'
";
$count_exams_res = $conn->query($count_exams_sql);
if ($count_exams_res && $count_exams_res->num_rows > 0) {
    $exams_row = $count_exams_res->fetch_assoc();
    $total_exams = $exams_row['total'];
}

// 3. جلب إحصائية عدد الطلاب الفريدين الذين اختبروا في امتحانات هذا المدرس فقط
$total_students = 0;
$count_students_sql = "
    SELECT COUNT(DISTINCT se.student_id) as total FROM student_exams se
    INNER JOIN exams e ON se.exam_id = e.exam_id
    INNER JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = '$instructor_id'
";
$count_students_res = $conn->query($count_students_sql);
if ($count_students_res && $count_students_res->num_rows > 0) {
    $students_row = $count_students_res->fetch_assoc();
    $total_students = $students_row['total'];
}

// 4. استعلام جلب الامتحانات (تم إضافة e.exam_token لجلب الرمز من القاعدة)
$exams_sql = "
    SELECT e.*, c.course_name, c.course_code 
    FROM exams e
    INNER JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = '$instructor_id'
    ORDER BY e.exam_id ASC
";
$exams_res = $conn->query($exams_sql);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chalkboard-user text-primary me-2"></i> لوحة تحكم الأستاذ</h2>
            <p class="text-muted mb-0">مرحباً بك مجدداً دكتور <span class="text-primary fw-bold"><?php echo $_SESSION['username'] ?? ''; ?></span>، يمكنك إدارة المقررات والأسئلة ومتابعة النتائج.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage_bank.php" class="btn btn-warning btn-lg shadow rounded-pill px-4 fw-bold text-dark">
                <i class="fa-solid fa-database me-2"></i> بنك الأسئلة الخاص بي
            </a>
            <a href="create_exam.php" class="btn btn-primary btn-lg shadow rounded-pill px-4 fw-bold" style="background-color: #003366; border: none;">
                <i class="fa-solid fa-plus-circle me-2"></i> إنشاء امتحان جديد
            </a>
        </div>
    </div>

    <hr class="mb-4">

    <div class="row g-4 mb-5">
        <div class="col-md-6 col-xl-4">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-semibold d-block mb-1">إجمالي الامتحانات المنشأة لمقرراتك</span>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_exams; ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fa-solid fa-file-signature fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-semibold d-block mb-1">الطلاب الذين تم اختبارهم</span>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_students; ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fa-solid fa-user-graduate fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
        <div class="card-header p-3 d-flex justify-content-between align-items-center border-0" style="background-color: #003366 !important;">
            <h5 class="mb-0 text-white fw-bold"><i class="fa-solid fa-list-check me-2 text-warning"></i> قائمة الامتحانات والمقررات الحالية</h5>
            <span class="badge bg-white text-dark px-3 py-2 fs-6">عدد الامتحانات: <?php echo ($exams_res) ? $exams_res->num_rows : 0; ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">رقم الامتحان</th>
                            <th class="py-3 text-start ps-4">عنوان الامتحان / المقرر</th>
                            <th class="py-3">المدة الزمنية</th>
                            <th class="py-3">حالة الامتحان</th>
                            <th class="py-3 text-end pe-4">إجراءات التحكم والتحرير</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($exams_res && $exams_res->num_rows > 0): ?>
                            <?php 
                            $i = 1; // العداد المتسلسل
                            while($exam = $exams_res->fetch_assoc()): 
                                
                                // جلب القيمة من حقل موعد الاختبار
                                $exam_date_raw = !empty($exam['exam_date']) ? trim($exam['exam_date']) : '';

                                // التحقق من أن الموعد تم تحديده وليس صفرياً
                                if (!empty($exam_date_raw) && strpos($exam_date_raw, '0000-00-00') === false) {
                                    $display_date = date('Y-m-d H:i', strtotime($exam_date_raw));
                                } else {
                                    $display_date = "لم يُحدد بعد";
                                }
                            ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?php echo $i++; ?></td>
                                    <td class="text-start ps-4">
                                        <span class="fw-bold text-dark d-block mb-1"><?php echo htmlspecialchars($exam['title']); ?></span>
                                        
                                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 small" style="font-size: 11px; font-weight: 600;">
                                                <i class="fa-solid fa-book me-1"></i> <?php echo !empty($exam['course_name']) ? htmlspecialchars($exam['course_name']) : 'مقرر عام'; ?> <?php echo !empty($exam['course_code']) ? "(".htmlspecialchars($exam['course_code']).")" : ''; ?>
                                            </span>
                                            
                                            <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-2 py-1 small" style="font-size: 11px; font-weight: 600; border: 1px dashed #6c757d;">
                                                <i class="fa-solid fa-key text-warning me-1"></i> رمز الدخول: <strong class="text-danger"><?php echo !empty($exam['exam_token']) ? htmlspecialchars($exam['exam_token']) : 'N/A'; ?></strong>
                                            </span>
                                        </div>
                                        
                                        <small class="text-secondary d-block" style="font-size: 11px; font-weight: 500;">
                                            <i class="fa-regular fa-clock text-danger me-1"></i> موعد الاختبار: <span class="fw-bold text-dark"><?php echo $display_date; ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                                            <i class="fa-regular fa-hourglass-half text-primary me-1"></i> <?php echo intval($exam['duration']); ?> دقيقة
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-2 bg-success bg-opacity-10 text-success">
                                            <i class="fa-solid fa-circle fa-2xs me-1"></i> نشط ومتاح
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group" role="group">
                                            <a href="link_exam_questions.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                                <i class="fa-solid fa-circle-question me-1"></i> الأسئلة
                                            </a>
                                            <a href="view_results.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-outline-success btn-sm px-3 fw-bold">
                                                <i class="fa-solid fa-square-poll-vertical me-1"></i> النتائج
                                            </a>
                                            <a href="edit_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-outline-secondary btn-sm px-2" title="تعديل">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a href="delete_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn btn-outline-danger btn-sm px-2" onclick="return confirm('⚠️ تحذير: هل أنت متأكد من حذف هذا الامتحان بجميع أسئلته ونتائج الطلاب المرتبطة به؟')" title="حذف">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-5 text-muted fs-5">
                                    <i class="fa-solid fa-folder-open fa-3x text-light-subtle d-block mb-3"></i>
                                    لا توجد امتحانات منشأة حالياً تحت حسابك للمقررات المسندة إليك. اضغط على "إنشاء امتحان جديد" للبدء.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .table-hover tbody tr { transition: background-color 0.2s ease; }
    .table-hover tbody tr:hover { background-color: #f8f9fa !important; }
    .btn-group .btn { transition: all 0.2s ease; }
    .card { border-radius: 15px; }
</style>

<?php
// استدعاء ملف الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>