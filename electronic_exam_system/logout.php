<?php
// 1. بدء الجلسة إذا لم تكن بدأت بعد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. إفراغ مصفوفة الجلسة تماماً من كافة البيانات (مثل student_id و user_id)
$_SESSION = array();

// 3. تدمير ملف تعريف الارتباط (Cookie) الخاص بالجلسة في متصفح المستخدم
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. تدمير الجلسة نهائياً من الخادم
session_destroy();

// 5. التوجيه إلى صفحة تسجيل الدخول الأساسية
header("Location: login.php");
exit();
?>