<?php
// تفعيل الجلسات لتتبع تسجيل دخول الطلاب والدكاترة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 🔥 [تحديث حاسم للسرعة]: تحويل الاسم من "localhost" إلى العنوان الرقمي الصريح "127.0.0.1"
// هذا التغيير يمنع الخادم المحلي من تضييع الوقت في ترجمة النطاق، ويجعل الاستجابة فائقة السرعة
$host = "127.0.0.1"; 
$user = "root";
$password = ""; // ضع كود المرور الخاص بقاعدتك إن وجد
$dbname = "online_exam_system";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ترميز اللغة العربية الشامل والآمن للرموز والتعبيرات
$conn->set_charset("utf8mb4");

// دالة حماية البيانات من الاختراقات (SQL Injection)
if (!function_exists('sanitize')) {
    function sanitize($data) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($data));
    }
}
?>