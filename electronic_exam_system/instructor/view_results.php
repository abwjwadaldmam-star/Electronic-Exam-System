<?php
// البدء بالجلسة وبناء الهيدر المشترك
ob_start();
include_once __DIR__ . '/../includes/header.php';

// ضبط النطاق الزمني لليمن
date_default_timezone_set('Asia/Aden');

// حماية الصفحة: المدرسين فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$instructor_id = intval($_SESSION['user_id']); 
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// 🛡️ 1. جلب تفاصيل الامتحان الحالي للتحقق من الوجود
$exam_info = null;
$exam_info_sql = "
    SELECT e.*, c.course_name 
    FROM exams e 
    LEFT JOIN courses c ON e.course_id = c.course_id 
    WHERE e.exam_id = ?
";
$stmt_exam = $conn->prepare($exam_info_sql);
if ($stmt_exam) {
    $stmt_exam->bind_param("i", $exam_id);
    $stmt_exam->execute();
    $exam_info_res = $stmt_exam->get_result();
    if ($exam_info_res && $exam_info_res->num_rows > 0) {
        $exam_info = $exam_info_res->fetch_assoc();
    }
}

if (!$exam_info) {
    echo "<div class='container mt-5'>";
    echo "  <div class='alert alert-danger text-center p-4 shadow rounded-3 fw-bold'>";
    echo "      ❌ عذراً، هذا الامتحان غير موجود في المنظومة حالياً!";
    echo "  </div>";
    echo "</div>"; // تم تصحيح القفل هنا وإزالة الـ </div> الزائد الذي كان خارج الـ echo
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}

// 🛡️ 2. الاستعلام الصحيح: ربط جدول المحاولات بجدول الطلاب ثم جدول المستخدمين لجلب البيانات الحقيقية
$results_sql = "
    SELECT 
        se.student_exam_id,
        se.student_id,
        se.start_time,
        se.status,
        u.full_name AS student_real_name,
        u.university_id AS student_code,
        r.total_obtained_marks,
        r.grade
    FROM student_exams se
    INNER JOIN students st ON se.student_id = st.student_id
    INNER JOIN users u ON st.user_id = u.user_id
    LEFT JOIN results r ON se.student_exam_id = r.student_exam_id
    WHERE se.exam_id = ?
    ORDER BY se.student_exam_id DESC
";

$stmt_results = $conn->prepare($results_sql);

if (!$stmt_results) {
    die("<div class='alert alert-danger m-5 text-center fw-bold'>خطأ في العلاقات أو أسماء الحقول: " . $conn->error . "</div>");
}

$stmt_results->bind_param("i", $exam_id);
$stmt_results->execute();
$results_res = $stmt_results->get_result();

// حساب الدرجة الكلية للامتحان
$total_exam_marks = isset($exam_info['total_marks']) ? intval($exam_info['total_marks']) : 0;
if ($total_exam_marks <= 0) {
    $total_marks_query = "SELECT SUM(marks) as real_total FROM questions WHERE exam_id = ?";
    $stmt_m = $conn->prepare($total_marks_query);
    if ($stmt_m) {
        $stmt_m->bind_param("i", $exam_id);
        $stmt_m->execute();
        $total_m_res = $stmt_m->get_result()->fetch_assoc();
        $total_exam_marks = isset($total_m_res['real_total']) ? intval($total_m_res['real_total']) : 5;
    } else {
        $total_exam_marks = 5;
    }
}
?>

<div class="container-fluid px-4 mt-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-square-poll-horizontal text-success me-2"></i> تقرير نتائج الطلاب</h3>
                    <p class="text-muted mb-0">
                        مقرر: <span class="text-primary fw-bold"><?php echo htmlspecialchars($exam_info['title'] ?? 'الامتحان الحالي'); ?></span> 
                        | الدرجة الكلية للاختبار: <span class="badge bg-danger fs-6 px-3"><?php echo $total_exam_marks; ?> درجة</span>
                    </p>
                </div>
                <button onclick="window.print();" class="btn btn-success shadow rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-print me-2"></i> طباعة كشف الدرجات
                </button>
            </div>

            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4 d-flex align-items-center justify-content-between">
                <span class="fw-bold fs-5"><i class="fa-solid fa-users me-2"></i> قوائم الطلاب الحاضرين للاختبار</span>
                <span class="badge bg-success fs-6">إجمالي المختبرين: <?php echo $results_res ? $results_res->num_rows : 0; ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center border">
                    <thead class="table-light fw-bold">
                        <tr>
                            <th class="py-3">م</th>
                            <th class="py-3 text-start ps-4">اسم الطالب (ID)</th>
                            <th class="py-3">تاريخ ووقت البدء</th>
                            <th class="py-3">حالة التسليم</th>
                            <th class="py-3">النتيجة والتقدير</th>
                            <th class="py-3">الدرجة المستحقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results_res && $results_res->num_rows > 0): ?>
                            <?php 
                            $m = 1;
                            while($row = $results_res->fetch_assoc()): 
                                
                                $student_exam_id = $row['student_exam_id'];
                                
                                // حساب الدرجة حياً في حال عدم وجودها بجدول النتائج
                                if ($row['total_obtained_marks'] !== null) {
                                    $score = intval($row['total_obtained_marks']);
                                    $display_score = $score;
                                    $display_grade = !empty($row['grade']) ? trim($row['grade']) : '---';
                                } else {
                                    $live_score_query = "
                                        SELECT SUM(q.marks) as live_total 
                                        FROM answers ans 
                                        INNER JOIN questions q ON ans.question_id = q.question_id 
                                        WHERE ans.student_exam_id = ? AND ans.is_correct = 1
                                    ";
                                    $stmt_live = $conn->prepare($live_score_query);
                                    $score = 0;
                                    if ($stmt_live) {
                                        $stmt_live->bind_param("i", $student_exam_id);
                                        $stmt_live->execute();
                                        $live_res = $stmt_live->get_result()->fetch_assoc();
                                        $score = isset($live_res['live_total']) ? intval($live_res['live_total']) : 0;
                                    }
                                    
                                    $display_score = $score;
                                    $display_grade = ($score >= ($total_exam_marks / 2)) ? 'ناجح' : 'راسب';
                                }
                                
                                $badge_class = "bg-secondary text-white";
                                if($display_grade == "ناجح" || $display_grade == "ناجحه") { $badge_class = "bg-success bg-opacity-10 text-success"; }
                                if($display_grade == "راسب" || $display_grade == "راسبه") { $badge_class = "bg-danger bg-opacity-10 text-danger"; }
                            ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $m++; ?></td>
                                    <td class="text-start ps-4">
                                        <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($row['student_real_name']); ?></span>
                                        <small class="text-muted">الرقم الأكاديمي: <?php echo htmlspecialchars($row['student_code']); ?> | ID الطالب: <?php echo $row['student_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="small text-secondary fw-semibold">
                                            <i class="fa-regular fa-calendar me-1"></i> <?php echo $row['start_time']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'completed'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                                                <i class="fa-solid fa-circle-check me-1"></i> مكتمل ومسلم
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2 animate-pulse">
                                                <i class="fa-solid fa-spinner fa-spin me-1"></i> سحب تلقائي / قيد الحل
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2 rounded-pill <?php echo $badge_class; ?> fw-bold">
                                            <?php echo $display_grade; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold fs-5 text-primary">
                                        <?php echo $display_score; ?> <span class="text-muted small fs-6">/ <?php echo $total_exam_marks; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-5 text-muted fs-5">
                                    <i class="fa-solid fa-graduation-cap fa-3x text-light-subtle d-block mb-3"></i>
                                    لم يقم أي طالب بدخول هذا الامتحان أو إنهائه حتى الآن في جدول النظام.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
include_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
?>