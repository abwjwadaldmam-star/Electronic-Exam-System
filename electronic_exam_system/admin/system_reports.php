<?php
// البدء بالجلسة واستدعاء الهيدر المشترك
ob_start();
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: السماح لمدير النظام (admin) فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 📊 1. استعلام جلب الامتحانات المنشأة في النظام مع المدرس والمادة التابعة لها
$exams_query = "
    SELECT e.exam_id, e.title, e.exam_date, e.total_marks, c.course_name, u.full_name AS instructor_name
    FROM exams e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
    LEFT JOIN users u ON i.user_id = u.user_id
    ORDER BY e.exam_id DESC
";
$exams_res = $conn->query($exams_query);

// 📈 2. الاستعلام الذكي والمصحح لجلب الجلسات القديمة (1, 19, 21, 22) دون النظر لاختلاف المعرفات
$results_query = "
    SELECT se.student_exam_id, se.start_time, se.status, 
           COALESCE(u_std.full_name, u_fallback.full_name, 'طالب تجريبي قديم') AS student_name, 
           COALESCE(u_std.university_id, u_fallback.university_id, 'N/A') AS student_code,
           e.title AS exam_title, r.score
    FROM student_exams se
    INNER JOIN exams e ON se.exam_id = e.exam_id
    LEFT JOIN students s ON se.student_id = s.student_id
    LEFT JOIN users u_std ON s.user_id = u_std.user_id
    LEFT JOIN users u_fallback ON se.student_id = u_fallback.user_id
    LEFT JOIN results r ON se.student_exam_id = r.student_exam_id
    ORDER BY se.student_exam_id DESC
";
$results_res = $conn->query($results_query);
?>

<div class="container-fluid px-4 mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill btn-sm mb-3">
        <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة التحكم الرئيسية
    </a>

    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-4 border">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-bar text-danger me-2"></i> تقارير النظام والرقابة الأكاديمية</h4>
            <p class="text-muted small mb-0">مراقبة الامتحانات الجارية ومتابعة سجلات نتائج الطلاب حياً من قاعدة البيانات</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fa-solid fa-file-invoice text-primary me-2"></i> أولاً: الامتحانات المنشأة بواسطة أعضاء هيئة التدريس
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle border text-center mb-0">
                <thead class="table-light fw-bold">
                    <tr>
                        <th class="py-3">معرف الامتحان</th>
                        <th class="py-3 text-start ps-4">عنوان الاختبار / المقرر</th>
                        <th class="py-3">تاريخ وموعد الاختبار</th>
                        <th class="py-3">الأستاذ المسؤول</th>
                        <th class="py-3">الدرجة الكلية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($exams_res && $exams_res->num_rows > 0): ?>
                        <?php while($exam = $exams_res->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-secondary">#<?php echo $exam['exam_id']; ?></td>
                                <td class="text-start ps-4">
                                    <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($exam['title']); ?></span>
                                    <small class="text-muted"><i class="fa-solid fa-book small me-1"></i> مقرر: <?php echo htmlspecialchars($exam['course_name']); ?></small>
                                </td>
                                <td class="text-muted small fw-semibold"><?php echo $exam['exam_date']; ?></td>
                                <td class="fw-bold text-primary"><i class="fa-solid fa-user-tie small me-1"></i> <?php echo htmlspecialchars($exam['instructor_name'] ?? 'غير محدد'); ?></td>
                                <td><span class="badge bg-primary px-3 py-2 rounded-pill fw-bold"><?php echo $exam['total_marks']; ?> درجة</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-4 text-muted">لا توجد امتحانات منشأة في المنظومة حالياً.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fa-solid fa-square-poll-horizontal text-success me-2"></i> ثانياً: سجلات دخول الاختبارات ونتائج الطلاب الإجمالية
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle border text-center mb-0">
                <thead class="table-light fw-bold">
                    <tr>
                        <th class="py-3">رقم الجلسة</th>
                        <th class="py-3 text-start ps-4">اسم الطالب / الرقم الأكاديمي</th>
                        <th class="py-3">الامتحان</th>
                        <th class="py-3">وقت البدء</th>
                        <th class="py-3">الحالة</th>
                        <th class="py-3">النتيجة المحققة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results_res && $results_res->num_rows > 0): ?>
                        <?php while($res_row = $results_res->fetch_assoc()): 
                            $status_badge = ($res_row['status'] === 'completed') ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning';
                            $status_text = ($res_row['status'] === 'completed') ? 'مكتمل' : 'قيد الاختبار';
                        ?>
                            <tr>
                                <td class="fw-bold text-secondary">#<?php echo $res_row['student_exam_id']; ?></td>
                                <td class="text-start ps-4">
                                    <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($res_row['student_name']); ?></span>
                                    <small class="text-muted"><i class="fa-solid fa-fingerprint me-1"></i> الرقم: <?php echo htmlspecialchars($res_row['student_code']); ?></small>
                                </td>
                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($res_row['exam_title']); ?></td>
                                <td class="text-muted small"><?php echo $res_row['start_time']; ?></td>
                                <td><span class="badge rounded-pill px-3 py-2 fw-bold <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                <td class="fw-bold fs-6 text-success">
                                    <?php echo ($res_row['score'] !== null) ? $res_row['score'] . " درجة" : "<span class='text-muted small'>لم ترصد بعد</span>"; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-4 text-muted">
                                <i class="fa-solid fa-magnifying-glass d-block mb-2 fa-xl text-light-subtle"></i>
                                لم يتم العثور على أي عمليات دخول للامتحانات مسجلة في جداول قاعدة البيانات حالياً.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
include_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
?>