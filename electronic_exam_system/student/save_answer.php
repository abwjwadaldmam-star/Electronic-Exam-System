<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once __DIR__ . '/../includes/config.php';
date_default_timezone_set('Asia/Aden');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

$student_id = intval($_SESSION['user_id']);

// الحفظ التلقائي أثناء الامتحان
if (isset($_POST['auto_save'])) {
    $exam_id = intval($_POST['exam_id']);
    $student_exam_id = intval($_POST['student_exam_id']);
    $question_id = intval($_POST['question_id']);
    $chosen_answer = trim($_POST['answer']);

    if ($exam_id > 0 && $question_id > 0) {
        $stmt_check = $conn->prepare("SELECT response_id FROM student_responses WHERE student_id = ? AND exam_id = ? AND question_id = ?");
        $stmt_check->bind_param("iii", $student_id, $exam_id, $question_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $stmt_update = $conn->prepare("UPDATE student_responses SET chosen_answer = ? WHERE student_id = ? AND exam_id = ? AND question_id = ?");
            $stmt_update->bind_param("siii", $chosen_answer, $student_id, $exam_id, $question_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO student_responses (student_id, exam_id, question_id, chosen_answer) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiis", $student_id, $exam_id, $question_id, $chosen_answer);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();
        echo json_encode(['status' => 'success']);
    }
    exit();
}

// التسليم النهائي للامتحان
if (isset($_POST['final_submit'])) {
    $exam_id = intval($_POST['exam_id']);
    $student_exam_id = intval($_POST['student_exam_id']);
    $answers = isset($_POST['answers']) ? $_POST['answers'] : [];

    // جلب بيانات الامتحان الأسية
    $exam_info_stmt = $conn->prepare("SELECT title FROM exams WHERE exam_id = ?");
    $exam_info_stmt->bind_param("i", $exam_id);
    $exam_info_stmt->execute();
    $exam_info = $exam_info_stmt->get_result()->fetch_assoc();
    $exam_info_stmt->close();
    $exam_title = $exam_info ? $exam_info['title'] : 'الامتحان النظري';

    // جلب الأسئلة وتصحيحها
    $q_stmt = $conn->prepare("SELECT question_id, correct_answer, marks FROM question_bank WHERE exam_id = ? AND status = 'approved'");
    $q_stmt->bind_param("i", $exam_id);
    $q_stmt->execute();
    $questions_res = $q_stmt->get_result();

    $total_score = 0;
    $max_exam_marks = 0;

    while ($q = $questions_res->fetch_assoc()) {
        $q_id = $q['question_id'];
        $db_correct_answer = strtolower(trim($q['correct_answer']));
        $q_marks = intval($q['marks']);
        $max_exam_marks += $q_marks;

        $student_answer = '';
        if (isset($answers[$q_id])) {
            $student_answer = strtolower(trim($answers[$q_id]));
        } else {
            $resp_stmt = $conn->prepare("SELECT chosen_answer FROM student_responses WHERE student_id = ? AND exam_id = ? AND question_id = ? LIMIT 1");
            $resp_stmt->bind_param("iii", $student_id, $exam_id, $q_id);
            $resp_stmt->execute();
            if ($resp_row = $resp_res->fetch_assoc()) {
                $student_answer = strtolower(trim($resp_row['chosen_answer']));
            }
            $resp_stmt->close();
        }

        if (!empty($student_answer)) {
            $save_stmt = $conn->prepare("SELECT response_id FROM student_responses WHERE student_id = ? AND exam_id = ? AND question_id = ?");
            $save_stmt->bind_param("iii", $student_id, $exam_id, $q_id);
            $save_stmt->execute();
            if ($save_stmt->get_result()->num_rows == 0) {
                $ins_resp = $conn->prepare("INSERT INTO student_responses (student_id, exam_id, question_id, chosen_answer) VALUES (?, ?, ?, ?)");
                $ins_resp->bind_param("iiis", $student_id, $exam_id, $q_id, $student_answer);
                $ins_resp->execute();
                $ins_resp->close();
            }
            $save_stmt->close();
        }

        if ($student_answer === $db_correct_answer) {
            $total_score += $q_marks;
        }
    }
    $q_stmt->close();

    // جلب وقت البدء
    $time_stmt = $conn->prepare("SELECT start_time FROM student_exams WHERE student_exam_id = ?");
    $time_stmt->bind_param("i", $student_exam_id);
    $time_stmt->execute();
    $time_row = $time_stmt->get_result()->fetch_assoc();
    $start_time = $time_row ? $time_row['start_time'] : date('Y-m-d H:i:s');
    $time_stmt->close();

    // تحديث حالة الامتحان في الداتا بيز إلى completed
    $end_time = date('Y-m-d H:i:s');
    $upd_exam = $conn->prepare("UPDATE student_exams SET end_time = ?, score = ?, status = 'completed' WHERE student_exam_id = ? AND student_id = ?");
    if ($upd_exam) {
        $upd_exam->bind_param("siii", $end_time, $total_score, $student_exam_id, $student_id);
        $upd_exam->execute();
        $upd_exam->close();
    }

    // تخزين البيانات المؤقتة في الجلسة لنقلها لصفحة العرض المستقلة
    $_SESSION['last_result'] = [
        'exam_title' => $exam_title,
        'total_score' => $total_score,
        'max_marks' => $max_exam_marks,
        'start_time' => $start_time,
        'end_time' => $end_time
    ];

    // التوجيه الفوري لصفحة الواجهة المنفصلة لكسر حلقة التحديث
    header("Location: result.php");
    exit();
}
?>