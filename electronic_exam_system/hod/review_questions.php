<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../includes/header.php';

// 1. التحقق من صلاحية رئيس القسم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head_of_dept') {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 2. جلب القسم الخاص برئيس القسم المسجل حالياً لتحديد صلاحية الرؤية
$dept_query = $conn->query("SELECT department FROM instructors WHERE user_id = '$current_user_id'");
$hod_dept = "";
if ($dept_query && $dept_query->num_rows > 0) {
    $dept_row = $dept_query->fetch_assoc();
    $hod_dept = $dept_row['department']; // سيحتوي على 'علوم حاسوب' أو 'تكنولوجيا المعلومات'
}

// 3. معالجة الاعتماد أو الاستبعاد عند النقر
// 3. معالجة الاعتماد أو الاستبعاد عند النقر (تم التطوير لمنع الـ NULL)
if (isset($_GET['action']) && isset($_GET['question_id'])) {
    $q_id = intval($_GET['question_id']);
    $action = $_GET['action'];
    $status_value = ($action === 'approve') ? 'approved' : 'rejected';
    
    if ($status_value === 'approved') {
        // أ: جلب رقم المقرر (course_id) الخاص بهذا السؤال أولاً
        $course_q = $conn->query("SELECT course_id FROM question_bank WHERE question_id = '$q_id'");
        if ($course_q && $course_q->num_rows > 0) {
            $c_row = $course_q->fetch_assoc();
            $c_id = $c_row['course_id'];
            
            // ب: البحث عن أحدث امتحان نشط مضاف لهذا المقرر
            $exam_q = $conn->query("SELECT exam_id FROM exams WHERE course_id = '$c_id' ORDER BY exam_id DESC LIMIT 1");
            if ($exam_q && $exam_q->num_rows > 0) {
                $e_row = $exam_q->fetch_assoc();
                $target_exam_id = $e_row['exam_id'];
                
                // ج: تحديث حالة السؤال وربطه برقم الامتحان مباشرة
                $conn->query("UPDATE question_bank SET status = 'approved', exam_id = '$target_exam_id' WHERE question_id = '$q_id'");
            } else {
                // إذا لم يكن هناك امتحان مضاف للمقرر بعد، يتم اعتماده في بنك الأسئلة العام
                $conn->query("UPDATE question_bank SET status = 'approved' WHERE question_id = '$q_id'");
            }
        }
    } else {
        // في حالة الاستبعاد (Reject)
        $conn->query("UPDATE question_bank SET status = 'rejected' WHERE question_id = '$q_id'");
    }

    echo "<script>alert('تم تحديث حالة السؤال وربطه بالامتحان بنجاح!'); window.location.href='review_questions.php';</script>";
    exit();
}

// 4. 🔥 [الاستعلام الذكي المصفي]: جلب الأسئلة التي تنتمي لقسم رئيس القسم الحالي فقط
$review_sql = "SELECT q.*, 
                      e.title as exam_title, 
                      u.full_name as instructor_name,
                      i.department as question_dept
               FROM question_bank q
               LEFT JOIN exams e ON q.exam_id = e.exam_id
               LEFT JOIN instructors i ON q.instructor_id = i.instructor_id
               LEFT JOIN users u ON i.user_id = u.user_id
               WHERE q.status = 'pending' AND i.department = '" . $conn->real_escape_string($hod_dept) . "'
               ORDER BY q.question_id DESC";
$review_res = $conn->query($review_sql);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-code-branch text-primary me-2"></i> طلبات مراجعة بنك الأسئلة</h3>
            <p class="text-muted mb-0">قسم: <span class="text-primary fw-bold"><?php echo htmlspecialchars($hod_dept); ?></span> | مراجعة واعتماد الأسئلة التابعة للقسم</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">العودة للوحة التحكم</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
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
                                <td><strong><?php echo htmlspecialchars($row['instructor_name'] ?? 'أستاذ المادة'); ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['exam_title'] ?? 'امتحان غير محدد'); ?></span></td>
                                <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($row['question_text']); ?></span></td>
                                <td><small class="text-muted"><?php echo $row['question_type'] === 'mcq' ? 'اختيار من متعدد' : ($row['question_type'] === 'truefalse' ? 'صح / خطأ' : 'مقالي'); ?></small></td>
                                <td><span class="badge bg-primary rounded-pill"><?php echo $row['marks']; ?> د</span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="review_questions.php?action=approve&question_id=<?php echo $row['question_id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">اعتماد</a>
                                        <a href="review_questions.php?action=reject&question_id=<?php echo $row['question_id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('هل أنت متأكد من استبعاد هذا السؤال؟')">استبعاد</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-square-check fa-3x text-success mb-3 opacity-50"></i>
                                <p class="mb-0 fw-bold">لا توجد أسئلة معلقة بانتظار الاعتماد لقسمك حالياً.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>