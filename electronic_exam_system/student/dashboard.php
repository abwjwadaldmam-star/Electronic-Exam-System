<?php
// استدعاء ملف الهيدر المشترك (يحتوي على الاتصال بالقاعدة والشعار وبدء الجلسة)
include_once __DIR__ . '/../includes/header.php';

// تأكيد بدء الجلسة بأمان إذا لم تكن قد بدأت في ملف header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🛡️ 1. منع المتصفح من حفظ الصفحة في الذاكرة المؤقتة (Cache) بعد تسجيل الخروج
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ⏳ 2. نظام التدمير التلقائي للجلسة عند الخمول (15 دقيقة = 900 ثانية)
$timeout_duration = 900; 

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // إفراغ وتدمير الجلسة تماماً لتنظيف المتصفح للطالب التالي
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: ../login.php?error=timeout");
    exit();
}
// تحديث طابع الوقت الحالي لآخر نشاط حقيقي للطالب
$_SESSION['LAST_ACTIVITY'] = time();


// 🛡️ 3. حماية الصفحة: التأكد من أن المستخدم مسجل دخول وأنه طالب فعلاً
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);
$full_name = htmlspecialchars($_SESSION['full_name']);
$university_id = htmlspecialchars($_SESSION['university_id']);

// 🛡️ 4. جلب تفاصيل الطالب الأكاديمية بأمان باستخدام Prepared Statements
$student_info_query = "SELECT level, department FROM students WHERE student_id = ?";
$stmt_info = $conn->prepare($student_info_query);
$stmt_info->bind_param("i", $student_id);
$stmt_info->execute();
$student_info_result = $stmt_info->get_result();
$student_info = $student_info_result->fetch_assoc();

$student_dept = $student_info['department'] ?? 'علوم حاسوب';
$student_lvl = $student_info['level'] ?? '4';
$stmt_info->close();
?>

<div class="row mb-4" dir="rtl">
    <div class="col-12">
        <div class="card card-custom p-4 text-white" style="background: linear-gradient(135deg, #003366 0%, #002244 100%); border-right: 5px solid #D4AF37; border-radius: 15px;">
            <div class="row align-items-center">
                <div class="col-md-8 text-right" style="text-align: right;">
                    <h2 class="fw-bold mb-2">أهلاً بك، <?php echo $full_name; ?></h2>
                    <p class="mb-0 text-white-50">لوحة التحكم الأكاديمية للامتحانات المحوسبة - قاعات جامعة إقليم سبأ</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0" style="text-align: left;">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 mb-1 d-inline-block">الرقم الجامعي: <?php echo $university_id; ?></span>
                    <br>
                    <small class="text-white-50">تخصص: <?php echo htmlspecialchars($student_dept); ?> | المستوى: <?php echo htmlspecialchars($student_lvl); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" dir="rtl">
    <div class="col-lg-8 mb-4">
        <div class="card card-custom p-4 bg-white h-100 shadow-sm" style="border-radius: 15px; text-align: right;">
            <h4 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-file-signature text-primary me-2"></i>الامتحانات الجارية والمتاحة</h4>
            
            <?php
            // 📊 ربط الامتحانات بمحاولات الطالب الحالي وتصفيتها بذكاء لعدم إخفاء بقية المواد
            $exams_query = "
                SELECT e.exam_id, e.title, e.exam_date, e.duration, c.course_name, c.course_code, se.status AS exam_status
                FROM exams e
                INNER JOIN courses c ON e.course_id = c.course_id
                LEFT JOIN student_exams se ON e.exam_id = se.exam_id AND se.student_id = ?
                WHERE se.status IS NULL OR se.status != 'completed'
                ORDER BY e.exam_id DESC
            ";
            
            $stmt_exams = $conn->prepare($exams_query);
            $stmt_exams->bind_param("i", $student_id);
            $stmt_exams->execute();
            $exams_result = $stmt_exams->get_result();

            if ($exams_result && $exams_result->num_rows > 0):
            ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-right" style="text-align: right;">
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
                                $exam_status = $exam['exam_status'];
                                
                                // 🔥 حساب مجموع درجات الامتحان الفعلي ديناميكياً من بنك الأسئلة المعتمد
                                $marks_query = "SELECT SUM(marks) as total FROM question_bank WHERE exam_id = ? AND status = 'approved'";
                                $stmt_m = $conn->prepare($marks_query);
                                $stmt_m->bind_param("i", $current_exam_id);
                                $stmt_m->execute();
                                $marks_res = $stmt_m->get_result()->fetch_assoc();
                                $exam_total_marks = isset($marks_res['total']) ? intval($marks_res['total']) : 100;
                                $stmt_m->close();
                            ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary d-block"><?php echo htmlspecialchars($exam['course_name']); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($exam['course_code']); ?></small>
                                    </td>
                                    <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($exam['title']); ?></span></td>
                                    <td><i class="fa-regular fa-clock me-1 text-secondary"></i> <?php echo intval($exam['duration']); ?> دقيقة</td>
                                    <td><span class="badge bg-secondary px-2 py-1"><?php echo $exam_total_marks; ?> درجة</span></td>
                                    <td class="text-center">
                                        <?php
                                        // 🛡️ التعديل الأمني هنا: توجيه كافة الإجراءات الحرة والمتابعة إلى صفحة بوابة التوكن والتأكد من الرمز
                                        if($exam_status === 'blocked') {
                                            echo '<span class="badge bg-danger py-2 px-3 rounded-pill"><i class="fa-solid fa-ban me-1"></i> محظور من المراقب</span>';
                                        } elseif($exam_status === 'active') {
                                            echo '<a href="exam_gateway.php?exam_id='.$current_exam_id.'" class="btn btn-warning btn-sm fw-bold px-3 rounded-pill">متابعة الامتحان</a>';
                                        } else {
                                            echo '<a href="exam_gateway.php?exam_id='.$current_exam_id.'" class="btn btn-primary btn-sm fw-bold px-3 rounded-pill" style="background-color: #003366;"><i class="fa-solid fa-right-to-bracket me-1"></i> دخول الامتحان</a>';
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
                    <i class="fa-solid fa-calendar-check text-muted fa-3x mb-3"></i>
                    <p class="text-muted fs-5">لا توجد امتحانات متاحة لك في الوقت الحالي.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4 mb-4" style="text-align: right;">
        <div class="card card-custom p-4 bg-white mb-4 shadow-sm" style="border-radius: 15px;">
            <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-square-poll-vertical text-warning me-2"></i>آخر النتائج المنشورة</h5>
            <hr>
            <?php
            // 🛡️ جلب آخر 3 نتائج حقيقية معلنة للطالب الحالي حصراً وبطريقة محمية
            $results_query = "
                SELECT r.total_obtained_marks, r.grade, e.title, e.exam_id
                FROM results r
                INNER JOIN student_exams se ON r.student_exam_id = se.student_exam_id
                INNER JOIN exams e ON se.exam_id = e.exam_id
                WHERE se.student_id = ?
                ORDER BY r.result_id DESC LIMIT 3
            ";
            $stmt_r = $conn->prepare($results_query);
            $stmt_r->bind_param("i", $student_id);
            $stmt_r->execute();
            $results_res = $stmt_r->get_result();

            if($results_res && $results_res->num_rows > 0):
                while($res_row = $results_res->fetch_assoc()):
                    $res_exam_id = intval($res_row['exam_id']);
                    
                    // 🔥 حساب المجموع الفعلي للاختبار في القائمة الجانبية من جدول البنك مباشرة
                    $tot_query = "SELECT SUM(marks) as total FROM question_bank WHERE exam_id = ? AND status = 'approved'";
                    $stmt_t = $conn->prepare($tot_query);
                    $stmt_t->bind_param("i", $res_exam_id);
                    $stmt_t->execute();
                    $tot_res = $stmt_t->get_result()->fetch_assoc();
                    $res_exam_total = isset($tot_res['total']) ? intval($tot_res['total']) : 100;
                    $stmt_t->close();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                    <div>
                        <strong class="d-block text-truncate" style="max-width: 180px; font-size:14px;"><?php echo htmlspecialchars($res_row['title']); ?></strong>
                        <small class="text-muted">الدرجة: <?php echo intval($res_row['total_obtained_marks']); ?> من <?php echo $res_exam_total; ?></small>
                    </div>
                    <span class="badge <?php echo ($res_row['grade'] === 'ناجح') ? 'bg-success' : 'bg-danger'; ?> fs-6"><?php echo htmlspecialchars($res_row['grade']); ?></span>
                </div>
            <?php 
                endwhile;
            else:
                echo '<p class="text-muted text-center py-3" style="font-size:14px;">لا توجد نتائج معلنة بعد.</p>';
            endif;
            $stmt_r->close();
            ?>
        </div>

        <div class="card card-custom p-4 bg-light border-0 shadow-sm" style="border-radius: 15px;">
            <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-shield-halved me-2"></i>تعليمات بيئة المعمل الأمنية:</h6>
            <ul class="text-muted pr-0" style="list-style-type: none; font-size: 13px; line-height: 1.8; padding-right: 0;">
                <li><i class="fa-solid fa-circle-info text-primary me-1"></i> يتم قفل الجلسة على الـ IP الخاص بجهازك فور بدء الامتحان.</li>
                <li><i class="fa-solid fa-circle-info text-primary me-1"></i> يمنع الخروج من صفحة الامتحان أو تحديثها دون إذن المراقب.</li>
                <li><i class="fa-solid fa-circle-info text-primary me-1"></i> أي محاولة تلاعب يسجلها النظام تلقائياً في سجلات لجنة الامتحانات.</li>
            </ul>
        </div>
    </div>
</div>

<?php
// استدعاء الفوتر المشترك لغلق وسوم الـ HTML
include_once __DIR__ . '/../includes/footer.php';
?>