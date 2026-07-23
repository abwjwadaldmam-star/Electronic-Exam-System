<?php
/**
 * نظام الحماية المركزي والتحقق من الصلاحيات (RBAC)
 * تم تطويره بمعايير هندسة البرمجيات لحماية واجهات النظام بالكامل
 */

// بدء الجلسة بأمان إذا لم تكن قد بدأت في الملفات السابقة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * دالة مركزية للتحقق من تسجيل الدخول وفحص نوع الصلاحية (Role)
 * @param array $allowed_roles مصفوفة تحتوي على الأدوار المسموح لها بدخول الصفحة
 */
function checkAccess($allowed_roles) {
    // 1. التحقق من أن المستخدم قد قام بتسجيل الدخول أولاً بالكامل
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // إذا لم يسجل الدخول، يتم توجيهه فوراً لصفحة تسجيل الدخول الرئيسية
        header("Location: ../login.php?error=login_required");
        exit();
    }

    // 2. تنظيف وجلب دور المستخدم الحالي من الجلسة لتجنب حساسيتها للأحرف
    $current_role = strtolower(trim($_SESSION['role']));

    // 3. التحقق مما إذا كان دور المستخدم الحالي موجوداً ضمن القائمة المسموح لها بالوصول
    if (!in_array($current_role, $allowed_roles)) {
        // إذا حاول مستخدم (طالب مثلاً) دخول صفحة غير مصرحة (كرئيس القسم)، يتم توجيهه لصفحة منع الوصول
        header("Location: ../unauthorized.php");
        exit();
    }
}

/**
 * دالة متطورة لفحص قيود النطاق الأكاديمي وعزل البيانات (Scope Isolation Constraint)
 * تضمن ألا يرى المستخدم إلا البيانات والتقارير التابعة لقسمه الأكاديمي فقط
 * @param string $data_department القسم الأكاديمي المرتبط بالبيانات المستعلم عنها من قاعدة البيانات
 * @return bool
 */
function isWithinScope($data_department) {
    // الأدمن وعميد الكلية يملكون صلاحيات مطلقة (Full Scope) لرؤية كافة الأقسام
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'dean') {
        return true;
    }
    
    // رؤساء الأقسام، الدكاترة، الكنترول، والطلاب؛ يتم فحص ومطابقة قسمهم المخزن في الجلسة حياً
    $user_department = isset($_SESSION['department']) ? $_SESSION['department'] : '';
    
    return (strtolower(trim($user_department)) === strtolower(trim($data_department)));
}
?>