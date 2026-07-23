<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات
include_once __DIR__ . '/../includes/header.php';

// ضبط النطاق الزمني لليمن
date_default_timezone_set('Asia/Aden');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. قراءة البيانات بشكل مرن جداً من الواجهة
    $exam_title = isset($_POST['exam_title']) ? trim($_POST['exam_title']) : '';
    if (empty($exam_title) && isset($_POST['title'])) { $exam_title = trim($_POST['title']); }
    if (empty($exam_title)) { $exam_title = "امتحان دوري جديد"; } 

    // جلب الـ course_id
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    if ($course_id <= 0 && isset($_POST['course'])) { $course_id = intval($_POST['course']); }
    
    if ($course_id <= 0) {
        $fallback = $conn->query("SELECT course_id FROM courses LIMIT 1");
        if ($fallback && $fallback->num_rows > 0) {
            $f_row = $fallback->fetch_assoc();
            $course_id = intval($f_row['course_id']);
        } else {
            $course_id = 1; 
        }
    }

    $duration  = isset($_POST['duration']) ? intval($_POST['duration']) : 60;
    $exam_date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : date('Y-m-d H:i:s');

    // 2. توليد رمز عشوائي (Token) للامتحان لتفادي فشل الحفظ في قاعدة البيانات
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $exam_token = '';
    for ($i = 0; $i < 5; $i++) {
        $exam_token .= $characters[rand(0, strlen($characters) - 1)];
    }

    // تجهيز النصوص لحمايتها
    $exam_title_escaped = $conn->real_escape_string($exam_title);
    $exam_date_escaped = $conn->real_escape_string($exam_date);
    $exam_token_escaped = $conn->real_escape_string($exam_token);
    
    // 3. الاستعلام الذهبي الشامل المتوافق مع الجدول بالـ token
    $insert_query = "INSERT INTO exams (title, course_id, duration, exam_date, exam_token, total_marks) 
                     VALUES ('$exam_title_escaped', $course_id, $duration, '$exam_date_escaped', '$exam_token_escaped', 0)";

    if ($conn->query($insert_query)) {
        $new_exam_id = $conn->insert_id;

        // ربط الأسئلة من البنك بالامتحان الجديد
        $conn->query("UPDATE question_bank SET exam_id = $new_exam_id WHERE course_id = $course_id");

        // الانتقال الفوري والصحيح لصفحة بنك الأسئلة بالرقم الحقيقي والمقرر الحقيقي
        header("Location: link_exam_questions.php?exam_id=" . $new_exam_id);
        exit();
    } else {
        // في حال وجود أي حقل إلزامي آخر لم نلاحظه
        die("خطأ متبقي في قاعدة البيانات: " . $conn->error);
    }
} else {
    header("Location: create_exam.php");
    exit();
}
?>