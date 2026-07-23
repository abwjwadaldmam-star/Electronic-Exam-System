<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id === 0) {
    echo json_encode(['success' => false, 'total' => 0]);
    exit();
}

// حساب المجموع الفعلي من جدول البنك للأسئلة المربوطة بهذا الامتحان
$query = "
    SELECT SUM(qb.marks) as total_marks 
    FROM exam_questions eq
    INNER JOIN question_bank qb ON eq.question_id = qb.question_id
    WHERE eq.exam_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total = isset($res['total_marks']) ? intval($res['total_marks']) : 0;

// تحديث جدول الامتحانات بالقيمة الجديدة لضمان سلامة التقارير
$update = $conn->prepare("UPDATE exams SET total_marks = ? WHERE exam_id = ?");
$update->bind_param("ii", $total, $exam_id);
$update->execute();

echo json_encode(['success' => true, 'total' => $total]);
exit();