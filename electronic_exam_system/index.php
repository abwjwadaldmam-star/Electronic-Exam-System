<?php
// تفعيل الجلسات لفحص حالة المستخدم
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. إذا كان المستخدم مسجل دخول بالفعل، يتم توجيهه حسب صلاحيته فوراً
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit();
        case 'instructor':
            header("Location: instructor/dashboard.php");
            exit();
        case 'student':
            header("Location: student/dashboard.php");
            exit();
    }
} else {
    // 2. إذا لم يكن مسجل دخول، يتم تحويله تلقائياً لصفحة تسجيل الدخول
    header("Location: login.php");
    exit();
}
?>