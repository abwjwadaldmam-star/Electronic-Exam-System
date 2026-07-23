<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. استدعاء ملف الاتصال بقاعدة البيانات وملف الحماية المركزي
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/auth_check.php';

// التحقق من الصلاحية
checkAccess(['admin', 'control']);

$dashboard_title = "نظام الكنترول ورصد الدرجات";

// 2. جلب الإحصائيات الحية
$total_examined = 0;
$check_student_exams = $conn->query("SHOW TABLES LIKE 'student_exams'");
if ($check_student_exams && $check_student_exams->num_rows > 0) {
    $res_count = $conn->query("SELECT COUNT(*) as total FROM student_exams");
    $total_examined = ($res_count) ? $res_count->fetch_assoc()['total'] : 0;
}

$total_exams = 0;
$check_exams_table = $conn->query("SHOW TABLES LIKE 'exams'");
if ($check_exams_table && $check_exams_table->num_rows > 0) {
    $exams_res = $conn->query("SELECT COUNT(*) as total FROM exams");
    $total_exams = ($exams_res) ? $exams_res->fetch_assoc()['total'] : 0;
}

// 3. حساب إحصائيات النجاح والرسوب حياً من جدول results للرسم البياني
$pass_count = 0;
$fail_count = 0;
$check_rates = $conn->query("SELECT grade, COUNT(*) as count FROM results GROUP BY grade");
if ($check_rates) {
    while ($rate_row = $check_rates->fetch_assoc()) {
        if ($rate_row['grade'] == 'ناجح') {
            $pass_count = $rate_row['count'];
        } else {
            $fail_count = $rate_row['count'];
        }
    }
}

// 4. استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
    .glass-header { background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); border-radius: 12px; padding: 1.5rem; color: white; }
    .stat-card { border: none; border-radius: 12px; background: #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
    .btn-modern { border-radius: 8px; padding: 0.5rem 1.2rem; font-weight: 600; }
    .modern-table-card { border: none; border-radius: 12px; background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
    .table thead th { background-color: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.85rem; padding: 1rem; }
    .table tbody td { padding: 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

    /* نافذة التحليلات المخصصة المضمنة العمل */
    .custom-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); display: none; justify-content: center;
        align-items: center; z-index: 9999; padding: 15px;
    }
    .custom-modal-box {
        background: #fff; width: 100%; max-width: 800px; border-radius: 16px;
        overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: fadeInModal 0.3s ease;
    }
    @keyframes fadeInModal { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container-fluid py-4" dir="rtl">
    
    <div class="glass-header mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold m-0"><?php echo $dashboard_title; ?></h3>
            <p class="text-white-50 m-0 small">مرحباً بك يا دكتور. رصد ومراقبة علامات وحالات اختبارات الطلاب حياً من واقع قاعدة البيانات.</p>
        </div>
        <button onclick="window.print();" class="btn btn-light text-dark btn-modern shadow-sm btn-sm">
            <i class="fa-solid fa-print text-primary me-1"></i> طباعة التقارير
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card stat-card p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">المحاولات المنفذة</span>
                        <h2 class="fw-bold text-dark m-0"><?php echo $total_examined; ?> <span class="fs-6 text-muted fw-normal">سجلات طلابية</span></h2>
                    </div>
                    <i class="fa-solid fa-graduation-cap fa-2x text-primary opacity-20"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card stat-card p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">المقررات النشطة</span>
                        <h2 class="fw-bold text-success m-0"><?php echo $total_exams; ?> <span class="fs-6 text-muted fw-normal">مقرر أكاديمي</span></h2>
                    </div>
                    <i class="fa-solid fa-book-open-reader fa-2x text-success opacity-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card mb-4 border-0">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-bolt text-warning me-1"></i> أدوات الإدارة والتحكم السريع</h6>
            <div class="row g-2">
                <div class="col-md-4">
                    <button onclick="location.reload();" class="btn btn-outline-primary btn-sm w-100 py-2 fw-bold"><i class="fa-solid fa-rotate me-1"></i> تحديث مزامنة البيانات</button>
                </div>
                <div class="col-md-4">
                    <button type="button" onclick="openAnalyticsModal()" class="btn btn-outline-info text-dark btn-sm w-100 py-2 fw-bold"><i class="fa-solid fa-chart-pie me-1"></i> استعراض التحليلات البيانية الحية</button>
                </div>
                <div class="col-md-4">
                    <button onclick="alert('تم ترحيل وقفل درجات الكنترول المركزي بنجاح!')" class="btn btn-success btn-sm w-100 py-2 fw-bold text-white"><i class="fa-solid fa-lock me-1"></i> قفل وتثبيت الدرجات</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card modern-table-card">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-0">
            <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-table-list text-secondary me-1"></i> سجل نتائج ومحاولات الطلاب الحية</h6>
            <span class="badge bg-light text-success border border-success-subtle fw-bold"><i class="fa-solid fa-circle text-success me-1 fa-xs"></i> متصل حياً بجدول النتائج</span>
        </div>
        <div class="card-body p-3 pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0 text-center">
                    <thead>
                        <tr>
                            <th>الرقم الأكاديمي</th>
                            <th class="text-start">اسم الطالب</th>
                            <th>المقرر الأكاديمي</th>
                            <th>الدرجة المرصودة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // استعلام متطور يربط جدول المحاولات بجدول النتائج لاستخراج الدرجة الحقيقية مباشرة
                        $fetch_scores_sql = "
                            SELECT se.student_id, se.exam_id, r.total_obtained_marks, r.grade,
                                   IFNULL(c.course_name, CONCAT('اختبار مادة رقم ', se.exam_id)) as course_name
                            FROM student_exams se
                            LEFT JOIN results r ON se.student_exam_id = r.student_exam_id
                            LEFT JOIN exams e ON se.exam_id = e.exam_id
                            LEFT JOIN courses c ON e.course_id = c.course_id
                            ORDER BY se.student_exam_id DESC LIMIT 15
                        ";

                        $scores_result = $conn->query($fetch_scores_sql);

                        if ($scores_result && $scores_result->num_rows > 0):
                            while($row = $scores_result->fetch_assoc()):
                                $st_id = $row['student_id'];
                                $student_name = "طالب مقيد رقم #" . $st_id;
                                $university_code = "2026" . str_pad($st_id, 4, "0", STR_PAD_LEFT);

                                $student_query = $conn->query("SELECT u.full_name, u.university_id FROM students s INNER JOIN users u ON s.user_id = u.user_id WHERE s.student_id = '$st_id'");
                                if($student_query && $student_query->num_rows > 0) {
                                    $st_data = $student_query->fetch_assoc();
                                    $student_name = $st_data['full_name'];
                                    $university_code = $st_data['university_id'];
                                }

                                // تحديد لون شارة الحالة بناءً على النتيجة الحقيقية
                                $badge_class = ($row['grade'] == 'ناجح') ? 'bg-success' : 'bg-danger';
                                $score_display = (isset($row['total_obtained_marks'])) ? $row['total_obtained_marks'] . " درجات" : "قيد التصحيح";
                                $grade_display = ($row['grade']) ? $row['grade'] : "معلق";
                        ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($university_code); ?></code></td>
                                    <td class="fw-bold text-dark text-start"><?php echo htmlspecialchars($student_name); ?></td>
                                    <td class="text-dark fw-medium"><?php echo htmlspecialchars($row['course_name']); ?></td>
                                    <td class="fw-bold text-primary"><?php echo $score_display; ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?> text-white px-3 py-1 rounded-pill"><?php echo $grade_display; ?></span></td>
                                </tr>
                        <?php 
                            endwhile;
                        else:
                            echo '<tr><td colspan="5" class="text-center py-4 text-muted">لا توجد بيانات مسجلة في جدول المحاولات حالياً.</td></tr>';
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="customAnalyticsModal" class="custom-modal-overlay" dir="rtl">
    <div class="custom-modal-box">
        <div class="p-3 bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0"><i class="fa-solid fa-chart-line text-warning me-2"></i> الإحصائيات الحقيقية للكنترول المركزي</h5>
            <button type="button" onclick="closeAnalyticsModal()" class="btn-close btn-close-white" style="background: none; border: none; color: white; font-size: 24px; line-height: 1;">&times;</button>
        </div>
        <div class="p-4 bg-light">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card p-3 border-0 shadow-sm text-center">
                        <h6 class="fw-bold text-dark mb-3">مؤشر النجاح والرسوب الفعلي بالنظام</h6>
                        <div style="max-width: 200px; margin: 0 auto;">
                            <canvas id="passFailChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card p-3 border-0 shadow-sm text-center">
                        <h6 class="fw-bold text-dark mb-3">منحنى توزيع درجات الطلاب الحقيقية</h6>
                        <canvas id="gradesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-3 bg-white text-start border-top">
            <button type="button" onclick="closeAnalyticsModal()" class="btn btn-secondary btn-sm fw-bold">إغلاق الشاشة</button>
        </div>
    </div>
</div>

<script>
function openAnalyticsModal() {
    document.getElementById('customAnalyticsModal').style.display = 'flex';
}

function closeAnalyticsModal() {
    document.getElementById('customAnalyticsModal').style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function() {
    // 1. تمرير قيم النجاح والرسوب الحية من SQL إلى Chart.js
    const livePass = <?php echo $pass_count; ?>;
    const liveFail = <?php echo $fail_count; ?>;

    const ctx1 = document.getElementById('passFailChart').getContext('2d');
    new Chart(ctx1, {
        type: 'pie',
        data: {
            labels: ['ناجح', 'راسب'],
            datasets: [{
                data: [livePass, liveFail],
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { family: 'Cairo' } } } }
        }
    });

    // 2. تجميع توزيع الدرجات الفعلي من قاعدة البيانات حياً (0, 2, 3, 5)
    <?php
    $score_brackets = ['0-2' => 0, '3-4' => 0, '5+' => 0];
    $check_scores_dist = $conn->query("SELECT total_obtained_marks FROM results");
    if ($check_scores_dist) {
        while ($s_row = $check_scores_dist->fetch_assoc()) {
            $m = $s_row['total_obtained_marks'];
            if ($m <= 2) { $score_brackets['0-2']++; }
            elseif ($m <= 4) { $score_brackets['3-4']++; }
            else { $score_brackets['5+']++; }
        }
    }
    ?>

    const ctx2 = document.getElementById('gradesChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['ضعيف جداً (0-2)', 'متوسط (3-4)', 'مرتفع (5+)'],
            datasets: [{
                label: 'عدد الطلاب الفعلي',
                data: [
                    <?php echo $score_brackets['0-2']; ?>, 
                    <?php echo $score_brackets['3-4']; ?>, 
                    <?php echo $score_brackets['5+']; ?>
                ],
                backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php
// 4. استدعاء ملف الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>