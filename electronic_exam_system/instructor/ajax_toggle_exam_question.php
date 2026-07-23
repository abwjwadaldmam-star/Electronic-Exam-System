<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// حماية الأمان
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

$exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($exam_id === 0 || $question_id === 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit();
}

if ($action === 'add') {
    // التحقق من عدم التكرار
    $check = $conn->prepare("SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ?");
    $check->bind_param("ii", $exam_id, $question_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $exam_id, $question_id);
        $stmt->execute();
    }
} else if ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = ? AND question_id = ?");
    $stmt->bind_param("ii", $exam_id, $question_id);
    $stmt->execute();
}

echo json_encode(['success' => true]);
exit();