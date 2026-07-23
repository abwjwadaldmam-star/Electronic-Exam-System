<?php
// استدعاء ملف الهيدر المشترك (يحتوي على config والاتصال بالقاعدة والشعار)
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: التأكد من أن المستخدم مسجل دخول وأنه طالب فعلاً
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // إذا لم يكن طالباً، يتم توجيهه لصفحة تسجيل الدخول فوراً
    header("Location: ../login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$university_id = $_SESSION['university_id'] ?? '';

// جلب تفاصيل الطالب الأكاديمية (القسم والمستوى) من جدول الطلاب
$student_info_query = "SELECT level, department FROM students WHERE student_id = '$student_id'";
$student_info_result = $conn->query($student_info_query);
$student_info = ($student_info_result && $student_info_result->num_rows > 0) ? $student_info_result->fetch_assoc() : ['level'=>'غير محدد', 'department'=>'غير محدد'];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom p-4 text-white" style="background: linear-gradient(135deg, #003366 0%, #002244 100%); border-right: 5px solid #D4AF37; border-radius: 15px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">أهلاً بك، <?php echo htmlspecialchars($full_name); ?></h2>
                    <p class="mb-0 text-white-50">لوحة التحكم الأكاديمية للامتحانات الإلكترونية - جامعة إقليم سبأ</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 mb-1 d-inline-block">الرقم الجامعي: <?php echo htmlspecialchars($university_id); ?></span>
                    <br>
                    <small class="text-white-50">تخصص: <?php echo htmlspecialchars($student_info['department']); ?> | المستوى: <?php echo htmlspecialchars($student_info['level']); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card card-custom p-4 bg-white h-100 shadow-sm border-0" style="border-radius: 15px;">
            <h4 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-file-signature text-primary me-2"></i>الامتحانات الجارية والمتاحة</h4>
            
            <?php
            // استعلام جلب الامتحانات الخاصة بالمواد المسجل فيها الطالب فقط
            $exams_query = "
                SELECT e.exam_id, e.title, e.exam_date, e.duration, e.total_marks, c.course_name, c.course_code 
                FROM enrollments en
                JOIN courses c ON en.course_id = c.course_id
                JOIN exams e ON c.course_id = e.course_id
                WHERE en.student_id = '$student_id'
                ORDER BY e.exam_date DESC
            ";
            $exams_result = $conn->query($exams_query);

            if ($exams_result && $exams_result->num_rows > 0):
            ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>المادة الدراسية</th>
                                <th>عنوان الامتحان</th>
                                <th>مدة الامتحان</th>
                                <th>الدرجة الكلية</th>
                                <th class="text-center">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($exam = $exams_result->fetch_assoc()): 
                                $current_exam_id = intval($exam['exam_id']);
                                
                                // حساب الدرجة الكلية ديناميكياً من جدول الأسئلة لتفادي عرض الصفر في جدول المتاحة
                                $q_total_query = "SELECT SUM(marks) as real_total FROM questions WHERE exam_id = '$current_exam_id'";
                                $q_total_res = $conn->query($q_total_query);
                                $display_total = 0;
                                if ($q_total_res && $q_total_res->num_rows > 0) {
                                    $q_row = $q_total_res->fetch_assoc();
                                    $display_total = isset($q_row['real_total']) ? intval($q_row['real_total']) : 0;
                                }
                                if ($display_total <= 0) { $display_total = 5; }
                            ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary d-block"><?php echo htmlspecialchars($exam['course_name']); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($exam['course_code']); ?></small>
                                    </td>
                                    <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($exam['title']); ?></span></td>
                                    <td><i class="fa-regular fa-clock me-1 text-secondary"></i> <?php echo intval($exam['duration']); ?> دقيقة</td>
                                    <td><span class="badge bg-secondary px-2 py-1"><?php echo $display_total; ?> درجة</span></td>
                                    <td class="text-center">
                                        <?php
                                        // التحقق من حالة الجلسة الاختبارية لهذا الطالب
                                        $check_exam_sql = "SELECT status FROM student_exams WHERE student_id = '$student_id' AND exam_id = '$current_exam_id'";
                                        $check_res = $conn->query($check_exam_sql);
                                        
                                        if($check_res && $check_res->num_rows > 0) {
                                            $status_row = $check_res->fetch_assoc();
                                            $status = $status_row['status'];
                                            
                                            if($status == 'completed') {
                                                echo '<span class="badge bg-success bg-opacity-10 text-success py-2 px-3 rounded-pill fw-bold"><i class="fa-solid fa-check-double me-1"></i> تم تسليم الإجابة</span>';
                                            } elseif($status == 'blocked') {
                                                echo '<span class="badge bg-danger bg-opacity-10 text-danger py-2 px-3 rounded-pill fw-bold"><i class="fa-solid fa-ban me-1"></i> محظور من المراقب</span>';
                                            } else {
                                                echo '<a href="exam_gateway.php?exam_id='.$current_exam_id.'" class="btn btn-warning btn-sm fw-bold px-3 rounded-pill shadow-sm">متابعة الامتحان</a>';
                                            }
                                        } else {
                                            echo '<a href="exam_gateway.php?exam_id='.$current_exam_id.'" class="btn btn-primary btn-sm fw-bold px-3 rounded-pill shadow-sm" style="background-color: #003366; border-color:#003366;"><i class="fa-solid fa-right-to-bracket me-1"></i> دخول الامتحان</a>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-calendar-check text-muted fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted fs-5">لا توجد امتحانات متاحة لك في الوقت الحالي.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card card-custom p-4 bg-white mb-4 shadow-sm border-0" style="border-radius: 15px;">
            <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-square-poll-vertical text-warning me-2"></i>آخر النتائج المنشورة</h5>
            <hr>
            <?php
            // الربط الصحيح والمطور مع جدول النتائج الحقيقي لضمان ظهور البيانات الفورية وعدم اختفائها
            $results_query = "
                SELECT r.total_obtained_marks, r.grade, e.title, e.exam_id, se.student_exam_id
                FROM results r
                JOIN student_exams se ON r.student_exam_id = se.student_exam_id
                JOIN exams e ON se.exam_id = e.exam_id
                WHERE se.student_id = '$student_id'
                ORDER BY r.published_date DESC LIMIT 3
            ";
            $results_res = $conn->query($results_query);

            if($results_res && $results_res->num_rows > 0):
                while($res_row = $results_res->fetch_assoc()):
                    $exam_id = intval($res_row['exam_id']);
                    $score = intval($res_row['total_obtained_marks']);
                    $grade = $res_row['grade'];
                    
                    // حساب إجمالي درجات الامتحان الفعلي التابع للأسئلة بشكل حي
                    $total_query = "SELECT SUM(marks) as real_total FROM questions WHERE exam_id = '$exam_id'";
                    $total_res = $conn->query($total_query);
                    $real_total = 0;
                    if ($total_res && $total_res->num_rows > 0) {
                        $t_row = $total_res->fetch_assoc();
                        $real_total = isset($t_row['real_total']) ? intval($t_row['real_total']) : 0;
                    }
                    if ($real_total <= 0) { $real_total = 5; }
                    
                    $is_passed = ($grade == 'ناجح' || $score >= ($real_total / 2));
            ?>
                <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded" style="border-right: 4px solid <?php echo $is_passed ? '#198754' : '#dc3545'; ?>;">
                    <div>
                        <strong class="d-block text-truncate text-dark mb-1" style="max-width: 170px; font-size:14px;"><?php echo htmlspecialchars($res_row['title']); ?></strong>
                        <div style="font-size: 13px; color: #6c757d; display: inline-flex; flex-direction: row-reverse; align-items: center; gap: 3px;">
                            <span>من <?php echo $real_total; ?></span>
                            <span class="fw-bold text-primary"><?php echo $score; ?></span>
                            <span style="margin-left: 2px;">الدرجة:</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge <?php echo $is_passed ? 'bg-success' : 'bg-danger'; ?> px-2 py-1 mb-1 d-inline-block" style="font-size: 11px;">
                            <?php echo htmlspecialchars($grade); ?>
                        </span>
                        <a href="result.php?student_exam_id=<?php echo $res_row['student_exam_id']; ?>" class="d-block text-primary small" style="text-decoration: none; font-size: 11px;">
                            <i class="fa-solid fa-eye me-1"></i> التفاصيل
                        </a>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
                echo '<p class="text-muted text-center py-3" style="font-size:14px;">لا توجد نتائج معلنة بعد.</p>';
            endif;
            ?>
        </div>

        <div class="card card-custom p-4 bg-white border-0 shadow-sm" style="border-radius: 15px;">
            <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-shield-halved me-2"></i>تعليمات بيئة المعمل الأمنية:</h6>
            <ul class="text-muted ps-0 mb-0" style="list-style-type: none; font-size: 13px; line-height: 2;">
                <li class="mb-2"><i class="fa-solid fa-circle-exclamation text-primary me-2"></i> يتم قفل الجلسة على الـ IP الخاص بجهازك فور بدء الامتحان.</li>
                <li class="mb-2"><i class="fa-solid fa-circle-exclamation text-primary me-2"></i> يمنع الخروج من صفحة الامتحان أو تحديثها دون إذن المراقب.</li>
                <li><i class="fa-solid fa-circle-exclamation text-primary me-2"></i> أي محاولة تلاعب يسجلها النظام تلقائياً في السجلات الحساسة للجنة.</li>
            </ul>
        </div>
    </div>
</div>

<?php
// استدعاء الفوتر المشترك لغلق وسوم الـ HTML وحفظ الحقوق
include_once __DIR__ . '/../includes/footer.php';
?>