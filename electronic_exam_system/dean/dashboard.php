<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/auth_check.php';

// مسموح فقط للعميد والأدمن
checkAccess(['admin', 'dean']);

// استعلام جلب إجمالي الكلية بالكامل (دون شروط الأقسام لإظهار القوة والتحكم الشامل للعميد)
$total_std = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'] ?? 0;
$total_inst = $conn->query("SELECT COUNT(*) as total FROM instructors")->fetch_assoc()['total'] ?? 0;

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4" dir="rtl">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
        <h4 class="h4 mb-0 text-dark fw-bold"><i class="fa-solid fa-building-columns text-primary me-2"></i> لوحة الإدارة العليا - عمادة الكلية</h4>
        <span class="badge bg-dark text-warning px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-eye me-1"></i> نظرة عامة وشاملة</span>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 bg-primary text-white shadow p-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 fw-bold">إجمالي مقاعد الطلاب بالكلية</h6>
                        <h2 class="fw-bold mb-0"><?php echo $total_std; ?> طالب ونظام فعال</h2>
                    </div>
                    <i class="fa-solid fa-users fa-3x text-white-50"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 bg-dark text-white shadow p-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-warning fw-bold">إجمالي الكادر التدريسي والأكاديمي</h6>
                        <h2 class="fw-bold mb-0"><?php echo $total_inst; ?> عضو هيئة تدريس</h2>
                    </div>
                    <i class="fa-solid fa-id-card-clip fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>