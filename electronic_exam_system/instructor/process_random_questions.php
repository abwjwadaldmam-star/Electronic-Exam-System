<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = intval($_POST['exam_id']);
    $course_id = intval($_POST['course_id']);
    $count = intval($_POST['questions_count']);

    if ($exam_id > 0 && $course_id > 0 && $count > 0) {
        
        // 💡 تم دمج $count مباشرة هنا بعد التأكد من أنه رقم صحيح لتجنب مشكلة LIMIT ? في المحرك
        $query = "
            SELECT question_id FROM question_bank 
            WHERE course_id = ? AND status = 'approved' 
            AND question_id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
            ORDER BY RAND() LIMIT $count
        ";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ii", $course_id, $exam_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $insert_stmt = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
                while ($row = $result->fetch_assoc()) {
                    $insert_stmt->bind_param("ii", $exam_id, $row['question_id']);
                    $insert_stmt->execute();
                }
                $insert_stmt->close();
            }
            $stmt->close();
        } else {
            // في حال وجود خطأ في أسماء الجداول لكي تكتشفه فوراً
            die("خطأ في قاعدة البيانات: " . $conn->error);
        }
    }
    
    header("Location: link_exam_questions.php?exam_id=" . $exam_id);
    exit();
}