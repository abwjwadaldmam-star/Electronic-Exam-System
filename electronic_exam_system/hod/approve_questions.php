<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الهيدر والاتصال
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: التأكد من أن المستخدم رئيس قسم أو مسؤول (يمكنك تعديل الشرط حسب الصلاحيات لديك)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'chairman' && $_SESSION['role'] !== 'instructor')) {
    header("Location: ../login.php");
    exit();
}

// معالجة عملية الاعتماد أو الاستبعاد عند النقر على الأزرار
if (isset($_GET['action']) && isset($_GET['question_id'])) {
    $q_id = intval($_GET['question_id']);
    $action = $_GET['action'];
    $status_value = ($action === 'approve') ? 'approved' : 'rejected';
    
    $update_status_sql = "UPDATE question_bank SET status = '$status_value' WHERE question_id = '$q_id'";
    if ($conn->query($update_status_sql)) {
        echo "<script>alert('تم تحديث حالة السؤال بنجاح!'); window.location.href='approve_questions.php';</script>";
        exit();
    }
}

// جلب كافة الأسئلة التي قيد الانتظار حالياً في النظام لمراجعتها
$review_sql = "SELECT q.*, e.title as exam_title, u.name as instructor_name 
               FROM question_bank q
               JOIN exams e ON q.exam_id = e.exam_id
               JOIN instructors i ON q.instructor_id = i.instructor_id
               JOIN users u ON i.user_id = u.user_id
               WHERE q.status = 'pending' 
               ORDER BY q.question_id DESC";
$review_res = $conn->query($review_sql);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-graduation-cap text-success me-2"></i> لوحة جودة واعتماد الأسئلة (رئيس القسم)</h3>
            <p class="text-muted mb-0">مراجعة الأسئلة المرفوعة من أعضاء هيئة التدريس والموافقة عليها</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-secondary mb-4 border-bottom pb-2">الأسئلة الواردة وقيد الانتظار (<?php echo ($review_res) ? $review_res->num_rows : 0; ?>)</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الأستاذ</th>
                        <th>الامتحان</th>
                        <th>نص السؤال</th>
                        <th>النوع</th>
                        <th>الدرجة</th>
                        <th class="text-center">الإجراء المتاح</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($review_res && $review_res->num_rows > 0): ?>
                        <?php while($row = $review_res->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['instructor_name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['exam_title']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['question_text']); ?></td>
                                <td>
                                    <span class="small text-muted">
                                        <?php echo $row['question_type'] === 'mcq' ? 'اختيار من متعدد' : ($row['question_type'] === 'truefalse' ? 'صح / خطأ' : 'مقالي'); ?>
                                    </span>
                                </td>
                                <td><span class="fw-bold text-primary"><?php echo $row['marks']; ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="approve_questions.php?action=approve&question_id=<?php echo $row['question_id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                            <i class="fa-solid fa-check me-1"></i> اعتماد
                                        </a>
                                        <a href="approve_questions.php?action=reject&question_id=<?php echo $row['question_id']; ?>" class="btn btn-danger btn-sm rounded-pill px-3" onclick="return confirm('هل أنت متأكد من استبعاد هذا السؤال؟')">
                                            <i class="fa-solid fa-xmark me-1"></i> استبعاد
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fa-3x text-success bg-opacity-10 mb-3"></i>
                                <p class="mb-0 fw-semibold">ممتاز! لا توجد أسئلة معلقة بانتظار المراجعة حالياً.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>