<?php
// تفعيل الجلسة بأمان إذا لم تكن بدأت بعد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الإعدادات والاتصال بالقاعدة
include_once __DIR__ . '/../includes/config.php';

// ضبط النطاق الزمني لليمن لضمان تطابق حسابات الوقت في السيرفر وقاعدة البيانات
date_default_timezone_set('Asia/Aden');

// حماية الصفحة والتأكد من هوية الطالب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['student_exam_id'])) {
    $student_exam_id = intval($_POST['student_exam_id']);
    $student_id = isset($_SESSION['student_id']) ? intval($_SESSION['student_id']) : 0;

    // 🛡️ [حصن النظام الزمني]: التحقق الصارم من وقت الامتحان عبر السيرفر لمنع التلاعب بالعداد
    $time_check_sql = "
        SELECT se.start_time, e.duration 
        FROM student_exams se 
        INNER JOIN exams e ON se.exam_id = e.exam_id 
        WHERE se.student_exam_id = ? AND se.student_id = ?
    ";
    $stmt_time = $conn->prepare($time_check_sql);
    $stmt_time->bind_param("ii", $student_exam_id, $student_id);
    $stmt_time->execute();
    $time_res = $stmt_time->get_result()->fetch_assoc();

    if ($time_res) {
        $start_time = strtotime($time_res['start_time']);
        $duration_seconds = intval($time_res['duration']) * 60;
        $current_time = time();
        
        // إعطاء مهلة 45 ثانية كحد أقصى لتأخر استجابة الشبكة أثناء الإرسال الحاشد لـ Form
        $allowed_time = $start_time + $duration_seconds + 45;

        if ($current_time > $allowed_time) {
            // قفل المحاولة فوراً وحرمان الطالب لحماية مصداقية الاختبار
            $block_sql = "UPDATE student_exams SET status = 'completed', end_time = NOW() WHERE student_exam_id = ?";
            $stmt_block = $conn->prepare($block_sql);
            $stmt_block->bind_param("i", $student_exam_id);
            $stmt_block->execute();

            echo "<script>
                alert('❌ تم رفض استلام ورقة الإجابة لتجاوزك الوقت القانوني المحدد للامتحان!');
                window.location.href = 'student_dashboard.php';
            </script>";
            exit();
        }
    }

    // 1. جلب الإجابات المباشرة المرسلة فوراً من واجهة الامتحان وحفظها
    $student_answers = isset($_POST['answers']) ? $_POST['answers'] : [];
    
    if (is_array($student_answers) && !empty($student_answers)) {
        foreach ($student_answers as $question_id => $selected_choice_id) {
            $question_id = intval($question_id);
            $selected_choice_id = intval($selected_choice_id);
            
            if ($question_id <= 0 || $selected_choice_id <= 0) continue;

            // التحقق من وجود السجل مسبقاً (لتجنب تكرار الصفوف وتداخل البيانات)
            $check_exist = "SELECT answer_id FROM answers WHERE student_exam_id = ? AND question_id = ?";
            $stmt_exist = $conn->prepare($check_exist);
            $stmt_exist->bind_param("ii", $student_exam_id, $question_id);
            $stmt_exist->execute();
            $exist_res = $stmt_exist->get_result();
            
            if ($exist_res && $exist_res->num_rows > 0) {
                // تحديث الخيار الفعلي المسلم وتصفير الدرجة مؤقتاً لإعادة التصحيح الدقيق
                $update_ans = "UPDATE answers SET selected_choice_id = ?, obtained_marks = 0 WHERE student_exam_id = ? AND question_id = ?";
                $stmt_up = $conn->prepare($update_ans);
                $stmt_up->bind_param("iii", $selected_choice_id, $student_exam_id, $question_id);
                $stmt_up->execute();
            } else {
                // إدخال سجل إجابة جديد تماماً
                $insert_ans = "INSERT INTO answers (student_exam_id, question_id, selected_choice_id, obtained_marks) VALUES (?, ?, ?, 0)";
                $stmt_ins = $conn->prepare($insert_ans);
                $stmt_ins->bind_param("iii", $student_exam_id, $question_id, $selected_choice_id);
                $stmt_ins->execute();
            }
        }
    }

    // 2. خطوة التصحيح التلقائي الصارم: مطابقة الإجابات مع مفتاح الحل المعتمد في البنك
    $fetch_saved_answers = "SELECT answer_id, question_id, selected_choice_id FROM answers WHERE student_exam_id = ?";
    $stmt_fetch = $conn->prepare($fetch_saved_answers);
    $stmt_fetch->bind_param("i", $student_exam_id);
    $stmt_fetch->execute();
    $saved_res = $stmt_fetch->get_result();

    if ($saved_res && $saved_res->num_rows > 0) {
        while ($ans_row = $saved_res->fetch_assoc()) {
            $ans_id = $ans_row['answer_id'];
            $q_id = $ans_row['question_id'];
            $choice_id = $ans_row['selected_choice_id'];

            // حماية إضافية: إذا كان الخيار صفراً، نحاول استرجاع البديل من الـ POST لحماية الطالب عند ضعف الشبكة
            if ($choice_id <= 0 && isset($student_answers[$q_id])) {
                $choice_id = intval($student_answers[$q_id]);
                $update_zero = "UPDATE answers SET selected_choice_id = ? WHERE answer_id = ?";
                $stmt_uz = $conn->prepare($update_zero);
                $stmt_uz->bind_param("ii", $choice_id, $ans_id);
                $stmt_uz->execute();
            }

            // 🔥 [تعديل حاسم]: جلب درجة السؤال الأصلية من جدول البنك المعتمد الحقيقي
            $marks_q = "SELECT marks FROM question_bank WHERE question_id = ?";
            $stmt_mq = $conn->prepare($marks_q);
            $stmt_mq->bind_param("i", $q_id);
            $stmt_mq->execute();
            $marks_res = $stmt_mq->get_result();
            
            $q_mark = 0; 
            if ($marks_res && $marks_res->num_rows > 0) {
                $mq_row = $marks_res->fetch_assoc();
                $q_mark = intval($mq_row['marks']);
            }

            // مطابقة خيار الطالب مع جدول الخيارات لمعرفة الصحة والإجابة النموذجية
            $verify_choice = "SELECT is_correct FROM choices WHERE choice_id = ? AND question_id = ?";
            $stmt_vc = $conn->prepare($verify_choice);
            $stmt_vc->bind_param("ii", $choice_id, $q_id);
            $stmt_vc->execute();
            $verify_res = $stmt_vc->get_result();
            
            $calculated_mark = 0;
            if ($verify_res && $verify_res->num_rows > 0) {
                $v_row = $verify_res->fetch_assoc();
                if (intval($v_row['is_correct']) === 1) {
                    $calculated_mark = $q_mark; // منحه الدرجة المقررة للسؤال كاملاً
                }
            }

            // تحديث الحقل بالدرجة النهائية المستحقة للسؤال في جدول الإجابات
            $update_mark = "UPDATE answers SET obtained_marks = ? WHERE answer_id = ?";
            $stmt_um = $conn->prepare($update_mark);
            $stmt_um->bind_param("ii", $calculated_mark, $ans_id);
            $stmt_um->execute();
        }
    }

    // 3. حساب إجمالي الدرجات التي حصل عليها الطالب فعلياً بعد انتهاء التصحيح
    $sum_sql = "SELECT SUM(obtained_marks) as final_score FROM answers WHERE student_exam_id = ?";
    $stmt_sum = $conn->prepare($sum_sql);
    $stmt_sum->bind_param("i", $student_exam_id);
    $stmt_sum->execute();
    $sum_res = $stmt_sum->get_result();
    
    $total_obtained_marks = 0;
    if ($sum_res) {
        $sum_row = $sum_res->fetch_assoc();
        $total_obtained_marks = isset($sum_row['final_score']) ? intval($sum_row['final_score']) : 0;
    }

    // 4. 🔥 [تعديل حاسم]: حساب المجموع الكلي الفعلي لدرجات الامتحان بالربط مع جدول البنك
    $total_exam_marks = 0;
    $exam_marks_sql = "
        SELECT SUM(q.marks) as total_marks 
        FROM answers a 
        JOIN question_bank q ON a.question_id = q.question_id 
        WHERE a.student_exam_id = ?
    ";
    $stmt_em = $conn->prepare($exam_marks_sql);
    $stmt_em->bind_param("i", $student_exam_id);
    $stmt_em->execute();
    $exam_marks_res = $stmt_em->get_result();
    
    if ($exam_marks_res) {
        $marks_row = $exam_marks_res->fetch_assoc();
        $total_exam_marks = isset($marks_row['total_marks']) ? intval($marks_row['total_marks']) : 0;
    }

    // تجنب القسمة على صفر في الحالات النادرة جداً
    if($total_exam_marks <= 0) { $total_exam_marks = 100; }

    // 5. إغلاق محاولة الطالب وتحديث حالتها إلى المكتملة وحفظ وقت النهاية الفعلي
    $update_session = "UPDATE student_exams SET status = 'completed', end_time = NOW() WHERE student_exam_id = ?";
    $stmt_us = $conn->prepare($update_session);
    $stmt_us->bind_param("i", $student_exam_id);
    $stmt_us->execute();

    // 6. حساب التقدير (ناجح/راسب) بناءً على النسبة المئوية المعتمدة (50%)
    $passing_mark = $total_exam_marks / 2;
    $grade = ($total_obtained_marks >= $passing_mark) ? 'ناجح' : 'راسب'; 

    // 7. تخزين أو تحديث النتيجة النهائية في جدول النتائج المعتمد للكنترول
    $check_res_exist = "SELECT result_id FROM results WHERE student_exam_id = ?";
    $stmt_cre = $conn->prepare($check_res_exist);
    $stmt_cre->bind_param("i", $student_exam_id);
    $stmt_cre->execute();
    $res_exist_res = $stmt_cre->get_result();

    if ($res_exist_res && $res_exist_res->num_rows > 0) {
        $insert_result = "
            UPDATE results 
            SET total_obtained_marks = ?, grade = ?, published_date = NOW()
            WHERE student_exam_id = ?
        ";
        $stmt_final = $conn->prepare($insert_result);
        $stmt_final->bind_param("isi", $total_obtained_marks, $grade, $student_exam_id);
    } else {
        $insert_result = "
            INSERT INTO results (student_exam_id, total_obtained_marks, grade, published_date)
            VALUES (?, ?, ?, NOW())
        ";
        $stmt_final = $conn->prepare($insert_result);
        $stmt_final->bind_param("iis", $student_exam_id, $total_obtained_marks, $grade);
    }
    $stmt_final->execute();

    // 8. التوجيه المباشر والآمن لصفحة النتيجة لعرض لوحة الشرف أو الدرجة المكتسبة
    header("Location: result.php?student_exam_id=" . $student_exam_id);
    exit();
} else {
    header("Location: student_dashboard.php");
    exit();
}
?>