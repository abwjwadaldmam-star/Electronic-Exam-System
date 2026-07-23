<?php
session_start();
// استدعاء ملف الاتصال بقاعدة البيانات

include_once __DIR__ . '/../includes/header.php';
// بقية كود لوحة التحكم الخاص بك...

// حماية الصفحة والتأكد من صلاحية المدرس
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// جلب رقم الامتحان المراد حذفه بأمان
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id > 0) {
    
    // 1. جلب كافة أسئلة هذا الامتحان لحذف خياراتها أولاً من جدول choices
    $questions_res = $conn->query("SELECT question_id FROM questions WHERE exam_id = '$exam_id'");
    if ($questions_res && $questions_res->num_rows > 0) {
        while ($q_row = $questions_res->fetch_assoc()) {
            $q_id = $q_row['question_id'];
            $conn->query("DELETE FROM choices WHERE question_id = '$q_id'");
        }
    }
    
    // 2. حذف الأسئلة المرتبطة بالامتحان من جدول questions
    $conn->query("DELETE FROM questions WHERE exam_id = '$exam_id'");
    
    // 3. حذف أي سجلات لنتائج الطلاب إن وجدت متعلقة بهذا الامتحان من جدول student_exams أو results
    $conn->query("DELETE FROM student_exams WHERE exam_id = '$exam_id'");
    
    // 4. حذف الامتحان نفسه الآن من جدول exams
    $delete_exam_sql = "DELETE FROM exams WHERE exam_id = '$exam_id'";
    
    if ($conn->query($delete_exam_sql)) {
        echo "<script>alert('تم حذف الامتحان وكافة الأسئلة والخيارات التابعة له بنجاح.'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء محاولة حذف الامتحان: " . $conn->error . "'); window.location.href='dashboard.php';</script>";
    }
    
} else {
    // في حال محاولة الدخول للملف بشكل خاطئ يتم إعادته للوحة التحكم
    header("Location: dashboard.php");
    exit();
}
?>