<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات (تأكد من المسار الصحيح للملف لديك)
include_once __DIR__ . '/../includes/header.php'; 

// حماية الصفحة والتأكد من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($question_id > 0 && $exam_id > 0) {
    
    // 1. حذف خيارات السؤال أولاً لمنع مشاكل القيود والـ Foreign Keys في جدول الخيارات
    $conn->query("DELETE FROM choices WHERE question_id = '$question_id'");
    
    // 2. 🔥 [التصحيح الحاسم]: حذف السؤال من جدول بنك الأسئلة الحقيقي بدلاً من جدول questions
    $delete_sql = "DELETE FROM question_bank WHERE question_id = '$question_id' AND exam_id = '$exam_id'";
    
    if ($conn->query($delete_sql)) {
        
        // 3. إعادة حساب المجموع الكلي لدرجات الامتحان بعد الحذف لتحديث العداد العلوي للامتحان
        $sum_res = $conn->query("SELECT SUM(marks) as total_sum FROM question_bank WHERE exam_id = '$exam_id'");
        $new_total_marks = 0;
        if ($sum_res) {
            $sum_row = $sum_res->fetch_assoc();
            $new_total_marks = isset($sum_row['total_sum']) ? intval($sum_row['total_sum']) : 0;
        }
        // تحديث جدول الامتحانات بالدرجة الجديدة
        $conn->query("UPDATE exams SET total_marks = '$new_total_marks' WHERE exam_id = '$exam_id'");

        echo "<script>alert('تم حذف السؤال من بنك الأسئلة وتحديث المجموع بنجاح!'); window.location.href='add_questions.php?exam_id=$exam_id';</script>";
        exit();
    } else {
        echo "<script>alert('حدث خطأ أثناء محاولة الحذف: " . $conn->error . "'); window.location.href='add_questions.php?exam_id=$exam_id';</script>";
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>