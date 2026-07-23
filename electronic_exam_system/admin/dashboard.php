<?php
// البدء بالجلسة واستدعاء الهيدر المشترك
ob_start();
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: السماح لمدير النظام (admin) فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 📊 جلب الإحصائيات حياً من قاعدة البيانات للعرض أمام اللجنة
// 1. إجمالي المستخدمين (طلاب ومدرسين)
$total_users = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM users");
if($res) { $total_users = $res->fetch_assoc()['count']; }

// 2. إجمالي الطلاب
$total_students = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM students");
if($res) { $total_students = $res->fetch_assoc()['count']; }

// 3. إجمالي المدرسين
$total_instructors = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM instructors");
if($res) { $total_instructors = $res->fetch_assoc()['count']; }

// 4. إجمالي المقررات (المواد)
$total_courses = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM courses");
if($res) { $total_courses = $res->fetch_assoc()['count']; }

// 5. إجمالي الامتحانات التي تم إنشاؤها
$total_exams = 0;
$res = $conn->query("SELECT COUNT(*) as count FROM exams");
if($res) { $total_exams = $res->fetch_assoc()['count']; }
?>

<div class="container-fluid px-4 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-dark text-white p-4 rounded-4 shadow-sm">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-user-shield text-warning me-2"></i> لوحة تحكم مدير النظام</h2>
            <p class="text-light text-opacity-75 mb-0">مرحباً بك: <?php echo $_SESSION['full_name'] ?? 'مدير النظام'; ?> | إدارة الصلاحيات والمنظومة الأكاديمية</p>
        </div>
        <div class="text-end">
            <span class="badge bg-warning text-dark fs-6 rounded-pill px-3 py-2">العام الجامعي: 2026</span>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1">أعضاء هيئة التدريس</h6>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_instructors; ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                        <i class="fa-solid fa-chalkboard-user fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1">إجمالي الطلاب المقيدين</h6>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_students; ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                        <i class="fa-solid fa-user-graduate fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1">المقررات الدراسية</h6>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_courses; ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3">
                        <i class="fa-solid fa-book-bookmark fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-danger border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1">الامتحانات المنشأة</h6>
                        <h2 class="fw-bold text-dark mb-0"><?php echo $total_exams; ?></h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-3">
                        <i class="fa-solid fa-file-signature fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h4 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-sliders me-2"></i> العمليات الإدارية المتاحة</h4>
    <div class="row g-4 mb-5">
        
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center h-100">
                <div class="text-primary mb-3">
                    <i class="fa-solid fa-users-gear fa-3x"></i>
                </div>
                <h5 class="fw-bold">إدارة حسابات المستخدمين</h5>
                <p class="text-muted small">إضافة وتعديل بيانات الطلاب، المدرسين، والمشرفين وتعيين كلمات المرور.</p>
                <a href="manage_users.php" class="btn btn-outline-primary rounded-pill w-100 mt-auto fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> الدخول للإدارة
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center h-100">
                <div class="text-success mb-3">
                    <i class="fa-solid fa-book-open-reader fa-3x"></i>
                </div>
                <h5 class="fw-bold">إدارة المقررات والمواد</h5>
                <p class="text-muted small">إضافة مواد خطة تكنولوجيا المعلومات ونظم المعلومات وإسنادها للمدرس المختص.</p>
                <a href="manage_courses.php" class="btn btn-outline-success rounded-pill w-100 mt-auto fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> الدخول للإدارة
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center h-100">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-chart-line fa-3x"></i>
                </div>
                <h5 class="fw-bold">تقارير النظام والامتحانات</h5>
                <p class="text-muted small">مراقبة الامتحانات الفعالة حالياً، ومراجعة كشوفات درجات الطلاب الإجمالية.</p>
                <a href="system_reports.php" class="btn btn-outline-danger rounded-pill w-100 mt-auto fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> عرض التقارير
                </a>
            </div>
        </div>

    </div>
</div>

<?php 
include_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
?>