<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. استدعاء ملفات التكوين والحماية المركزي
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/auth_check.php';

// التحقق من الصلاحية
checkAccess(['admin', 'head_of_dept']);

$dashboard_title = "لوحة تحكم رئيس القسم الأكاديمي";
$user_id = $_SESSION['user_id'] ?? 0;

// 2. جلب اسم القسم الخاص برئيس القسم الحالي
$department_name = "تقنية معلومات"; 
$dept_stmt = $conn->prepare("SELECT department FROM instructors WHERE user_id = ?");
if ($dept_stmt) {
    $dept_stmt->bind_param("i", $user_id);
    $dept_stmt->execute();
    $dept_res = $dept_stmt->get_result();
    if ($dept_res && $dept_res->num_rows > 0) {
        $instructor_data = $dept_res->fetch_assoc();
        if (!empty($instructor_data['department'])) {
            $department_name = $instructor_data['department'];
        }
    }
    $dept_stmt->close();
}

// 3. حساب الإحصائيات الحية للقسم
$total_students_in_dept = 0;
$student_stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE department = ?");
if ($student_stmt) {
    $student_stmt->bind_param("s", $department_name);
    $student_stmt->execute();
    $student_res = $student_stmt->get_result();
    if ($student_res) { $total_students_in_dept = $student_res->fetch_assoc()['total']; }
    $student_stmt->close();
}

$total_courses_in_dept = 0;
$course_stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE department = ?");
if ($course_stmt) {
    $course_stmt->bind_param("s", $department_name);
    $course_stmt->execute();
    $course_res = $course_stmt->get_result();
    if ($course_res) { $total_courses_in_dept = $course_res->fetch_assoc()['total']; }
    $course_stmt->close();
}

$total_instructors_in_dept = 0;
$inst_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM instructors WHERE department = ?");
if ($inst_count_stmt) {
    $inst_count_stmt->bind_param("s", $department_name);
    $inst_count_stmt->execute();
    $inst_count_res = $inst_count_stmt->get_result();
    if ($inst_count_res) { $total_instructors_in_dept = $inst_count_res->fetch_assoc()['total']; }
    $inst_count_stmt->close();
}

// 4. [جديد] حساب عدد الأسئلة المعلقة بانتظار الاعتماد في القسم حياً
$pending_questions_count = 0;
$q_count_sql = "
    SELECT COUNT(*) as total 
    FROM question_bank q
    INNER JOIN courses c ON q.course_id = c.course_id
    WHERE c.department = ? AND q.status = 'pending'
";
$q_count_stmt = $conn->prepare($q_count_sql);
if ($q_count_stmt) {
    $q_count_stmt->bind_param("s", $department_name);
    $q_count_stmt->execute();
    $q_count_res = $q_count_stmt->get_result();
    if ($q_count_res) {
        $pending_questions_count = $q_count_res->fetch_assoc()['total'];
    }
    $q_count_stmt->close();
}

// 5. استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
    .glass-header { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); border-radius: 12px; padding: 1.5rem; color: white; }
    .stat-card { border: none; border-radius: 12px; background: #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
    .task-card { border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; background: #ffffff; }
    .task-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.05); }
    .modern-table-card { border: none; border-radius: 12px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
    .table thead th { background-color: #f1f5f9; color: #475569; font-weight: 700; padding: 1rem; text-align: center; }
    .table tbody td { padding: 1rem; border-bottom: 1px solid #f1f5f9; text-align: center; }
</style>

<div class="container-fluid py-4" dir="rtl">
    
    <div class="glass-header mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold m-0"><?php echo $dashboard_title; ?></h3>
            <p class="text-white-50 m-0 small">رئيس قسم: <span class="text-warning fw-bold"><?php echo htmlspecialchars($department_name); ?></span> | الإشراف ومتابعة المهام الصلاحية.</p>
        </div>
        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> صلاحية رئيس قسم معتمدة</span>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">طلاب القسم المقيدين</span>
                        <h2 class="fw-bold text-dark m-0"><?php echo $total_students_in_dept; ?> <span class="fs-6 text-muted fw-normal">طالب</span></h2>
                    </div>
                    <i class="fa-solid fa-users fa-2x text-primary opacity-20"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">المقررات الأكاديمية</span>
                        <h2 class="fw-bold text-success m-0"><?php echo $total_courses_in_dept; ?> <span class="fs-6 text-muted fw-normal">مقرر</span></h2>
                    </div>
                    <i class="fa-solid fa-book fa-2x text-success opacity-20"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">أعضاء هيئة التدريس</span>
                        <h2 class="fw-bold text-warning m-0"><?php echo $total_instructors_in_dept; ?> <span class="fs-6 text-muted fw-normal">مدرس</span></h2>
                    </div>
                    <i class="fa-solid fa-chalkboard-user fa-2x text-warning opacity-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card p-4 mb-4 border-0 shadow-sm">
        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> مركز العمليات والمهام الإدارية لرئيس القسم</h5>
        <div class="row g-3">
            
            <div class="col-6 col-md-3">
                <div class="card task-card p-3 text-center border-top border-primary border-3 shadow-sm h-100" style="cursor:pointer;" onclick="alert('جاري الانتقال لصفحة مراجعة واعتماد المخططات وتوصيف المقررات الدراسي للقسم...')">
                    <div class="text-primary mb-2"><i class="fa-solid fa-file-signature fa-2x"></i></div>
                    <h6 class="fw-bold text-dark small m-0">اعتماد توصيف المقررات</h6>
                    <span class="badge bg-light text-primary border border-primary-subtle mt-2">8 مقررات بانتظارك</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card task-card p-3 text-center border-top border-success border-3 shadow-sm h-100" style="cursor:pointer;" onclick="location.href='review_questions.php'">
                    <div class="text-success mb-2"><i class="fa-solid fa-cubes fa-2x"></i></div>
                    <h6 class="fw-bold text-dark small m-0">مراجعة بنوك الأسئلة</h6>
                    <?php if ($pending_questions_count > 0): ?>
                        <span class="badge bg-danger text-white mt-2"><?php echo $pending_questions_count; ?> أسئلة معلقة</span>
                    <?php else: ?>
                        <span class="badge bg-light text-success border border-success-subtle mt-2">البنك معتمد ومستقر</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card task-card p-3 text-center border-top border-warning border-3 shadow-sm h-100" style="cursor:pointer;" onclick="alert('جاري الانتقال لإدارة نصاب وجداول أعضاء هيئة التدريس بالقسم...')">
                    <div class="text-warning mb-2"><i class="fa-solid fa-user-gear fa-2x"></i></div>
                    <h6 class="fw-bold text-dark small m-0">توزيع النصاب التدريسي</h6>
                    <span class="badge bg-light text-warning border border-warning-subtle mt-2">تعديل الصلاحيات</span>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card task-card p-3 text-center border-top border-danger border-3 shadow-sm h-100" style="cursor:pointer;" onclick="alert('جاري استخراج وإرسال تقارير الجودة الإحصائية الشاملة لعمادة الكلية...')">
                    <div class="text-danger mb-2"><i class="fa-solid fa-chart-line fa-2x"></i></div>
                    <h6 class="fw-bold text-dark small m-0">تقارير الجودة والمخرجات</h6>
                    <span class="badge bg-light text-danger border border-danger-subtle mt-2">تصدير PDF</span>
                </div>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card modern-table-card h-100">
                <div class="card-header bg-white py-3 px-3 border-0">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-graduation-cap text-primary me-2"></i> سجل ونتائج علامات طلاب القسم</h6>
                </div>
                <div class="card-body p-3 pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead>
                                <tr>
                                    <th>اسم الطالب</th>
                                    <th>المقرر</th>
                                    <th>الدرجة</th>
                                    <th>التقدير</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $results_sql = "
                                    SELECT u.full_name, c.course_name, r.total_obtained_marks, r.grade
                                    FROM results r
                                    INNER JOIN student_exams se ON r.student_exam_id = se.student_exam_id
                                    INNER JOIN students s ON se.student_id = s.student_id
                                    INNER JOIN users u ON s.user_id = u.user_id
                                    INNER JOIN exams e ON se.exam_id = e.exam_id
                                    INNER JOIN courses c ON e.course_id = c.course_id
                                    WHERE s.department = ?
                                    ORDER BY r.result_id DESC LIMIT 8
                                ";
                                $res_stmt = $conn->prepare($results_sql);
                                if ($res_stmt) {
                                    $res_stmt->bind_param("s", $department_name);
                                    $res_stmt->execute();
                                    $results_list = $res_stmt->get_result();
                                    if ($results_list && $results_list->num_rows > 0) {
                                        while ($row = $results_list->fetch_assoc()) {
                                            $badge = ($row['grade'] == 'ناجح') ? 'bg-success' : 'bg-danger';
                                            echo "<tr>";
                                            echo "<td class='fw-bold text-dark text-start'>" . htmlspecialchars($row['full_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                                            echo "<td class='text-primary fw-bold'>" . htmlspecialchars($row['total_obtained_marks']) . "</td>";
                                            echo "<td><span class='badge {$badge} text-white px-2 py-1 rounded-pill'>" . htmlspecialchars($row['grade']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center py-4 text-muted">لا توجد نتائج مرصودة للقسم حالياً.</td></tr>';
                                    }
                                    $res_stmt->close();
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card modern-table-card h-100">
                <div class="card-header bg-white py-3 px-3 border-0">
                    <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-user-tie text-warning me-2"></i> كادر التدريس التابع للقسم</h6>
                </div>
                <div class="card-body p-3 pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead>
                                <tr>
                                    <th class="text-start">اسم المدرس</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $inst_sql = "
                                    SELECT u.full_name, u.email 
                                    FROM instructors i 
                                    INNER JOIN users u ON i.user_id = u.user_id 
                                    WHERE i.department = ?
                                    LIMIT 8
                                ";
                                $inst_stmt = $conn->prepare($inst_sql);
                                if ($inst_stmt) {
                                    $inst_stmt->bind_param("s", $department_name);
                                    $inst_stmt->execute();
                                    $inst_list = $inst_stmt->get_result();
                                    if ($inst_list && $inst_list->num_rows > 0) {
                                        while ($inst_row = $inst_list->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold text-dark text-start'><i class='fa-solid fa-circle-user text-secondary me-1'></i> " . htmlspecialchars($inst_row['full_name']) . "</td>";
                                            echo "<td><small class='text-muted'>" . htmlspecialchars($inst_row['email'] ?? 'لا يوجد') . "</small></td>";
                                            echo "<td><span class='badge bg-light text-primary border border-primary-subtle rounded-pill'>نشط</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center py-4 text-muted">لا يوجد مدرسين مسجلين في هذا القسم.</td></tr>';
                                    }
                                    $inst_stmt->close();
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 7. استدعاء ملف الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>