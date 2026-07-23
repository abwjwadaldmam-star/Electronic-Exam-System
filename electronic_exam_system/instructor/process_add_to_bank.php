<?php
// بدء الجلسة قبل أي مخرجات
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الهيدر (الذي يحتوي على اتصال قاعدة البيانات $conn وحماية الجلسة)
include_once __DIR__ . '/../includes/header.php'; 

// حماية إضافية لضمان رتبة المدرس
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// 🔥 جلب معرّف المدرس المسجل حالياً من الجلسة (Session) لإنهاء مشكلة الصفر
$instructor_id = intval($_SESSION['user_id']);

// التحقق من استقبال البيانات عبر بروتوكول POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // استقبال وتنظيف البيانات الأساسية
    $course_id     = intval($_POST['course_id']);
    $question_text = trim($_POST['question_text']);
    $question_type = trim($_POST['question_type']);
    $marks         = isset($_POST['score']) ? intval($_POST['score']) : 1; // ربط درجة الحقل بـ marks
    
    // إعداد متغيرات الخيارات والإجابة الافتراضية كـ null
    $choice_1 = null;
    $choice_2 = null;
    $choice_3 = null;
    $choice_4 = null;
    $correct_answer = null;

    // معالجة البيانات بناءً على نوع السؤال المختار من الواجهة
    if ($question_type === 'mcq') {
        $choice_1 = trim($_POST['choice_1']);
        $choice_2 = trim($_POST['choice_2']);
        $choice_3 = trim($_POST['choice_3']);
        $choice_4 = trim($_POST['choice_4']);
        $correct_answer = trim($_POST['correct_choice']); // يحمل رقم الخيار الصحيح (1، 2، 3، أو 4)
        
    } elseif ($question_type === 'true_false' || $question_type === 'truefalse') {
        // 🔥 توحيد المسمى ليتطابق مع قاعدة البيانات truefalse
        $question_type = 'truefalse'; 
        $choice_1 = "صح (True)";
        $choice_2 = "خطأ (False)";
        $correct_answer = trim($_POST['correct_tf']); // يحمل القيمة (true أو false)
    }

    // 🛡️ [تم التطهير والتصحيح هنا]: تحديد 11 حاملاً للقيم متطابقين تماماً مع الرموز المرجعية بالأسفل
    $sql = "INSERT INTO question_bank (course_id, instructor_id, question_text, question_type, choice_1, choice_2, choice_3, choice_4, correct_answer, marks, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    if ($stmt = $conn->prepare($sql)) {
        
        $status_default = 'pending';
        
        // ربط المتغيرات بالترتيب: 11 نوعاً مرجعياً يقابلها 11 متغيراً بدقة متناهية
        $stmt->bind_param("iisssssssis", $course_id, $instructor_id, $question_text, $question_type, $choice_1, $choice_2, $choice_3, $choice_4, $correct_answer, $marks, $status_default);
        
        if ($stmt->execute()) {
            $stmt->close();
            // التوجيه الناجح والعودة لصفحة البنك فوراً لرؤية السؤال المضاف
            echo "<script>window.location.href='manage_bank.php?success=1';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger m-4'>❌ حدث خطأ أثناء الحفظ في قاعدة البيانات: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger m-4'>❌ خطأ في تحضير استعلام الإدخال: " . $conn->error . "</div>";
    }
} else {
    echo "<script>window.location.href='manage_bank.php';</script>";
    exit();
}

include_once __DIR__ . '/../includes/footer.php';
?>