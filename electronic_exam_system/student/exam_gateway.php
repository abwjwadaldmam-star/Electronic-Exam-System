<?php
// استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// التحقق من صحة معرف الامتحان ووجوده بالقاعدة
$exam_query = "SELECT e.*, c.course_name FROM exams e JOIN courses c ON e.course_id = c.course_id WHERE e.exam_id = '$exam_id'";
$exam_result = $conn->query($exam_query);

if ($exam_result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center mt-5'>عذراً، الامتحان غير موجود أو تم حذفه!</div>";
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}

$exam = $exam_result->fetch_assoc();
$error = "";

// فحص ما إذا كان الطالب قد أنشأ جلسة لهذا الامتحان مسبقاً
$check_session = $conn->query("SELECT status FROM student_exams WHERE student_id = '$student_id' AND exam_id = '$exam_id'");
if ($check_session->num_rows > 0) {
    $session_status = $check_session->fetch_assoc()['status'];
    if ($session_status == 'completed') {
        header("Location: dashboard.php");
        exit();
    } elseif ($session_status == 'active') {
        // إذا كانت الجلسة نشطة بالفعل (انقطع التيار أو أغلق المتصفح خطأً)، يتم توجيهه مباشرة للأسئلة مكملاً لوقته
        header("Location: quiz.php?exam_id=" . $exam_id);
        exit();
    }
}

// معالجة الضغط على زر "التحقق وبدء الامتحان"
if (isset($_POST['verify_token'])) {
    $input_token = sanitize($_POST['exam_token']);
    
    // جلب الـ IP الخاص بجهاز الطالب في المعمل
    $ip_address = $_SERVER['REMOTE_ADDR']; 

    // مطابقة الـ Token
    if ($input_token === $exam['exam_token']) {
        
        // إدراج الجلسة الجديدة في جدول student_exams لتوثيق بدء الامتحان وحماية المعمل
        $insert_session = "INSERT INTO student_exams (student_id, exam_id, start_time, ip_address, status) 
                           VALUES ('$student_id', '$exam_id', NOW(), '$ip_address', 'active')
                           ON DUPLICATE KEY UPDATE status='active', ip_address='$ip_address'";
        
        if ($conn->query($insert_session)) {
            // تسجيل الحركة الأمنية في جدول الـ LOGS لإبهار لجنة المناقشة
            $action_log = "الطالب دخل للامتحان [ " . $exam['title'] . " ] من جهاز ذو الأيبي: " . $ip_address;
            $conn->query("INSERT INTO logs (user_id, action) VALUES ('".$_SESSION['user_id']."', '$action_log')");
            
            // التوجيه لصفحة الاختبار الفعلي
            header("Location: quiz.php?exam_id=" . $exam_id);
            exit();
        } else {
            $error = "حدث خطأ أثناء إعداد جلسة الامتحان، يرجى مراجعة المراقب.";
        }
    } else {
        $error = "الرمز السري الفوري (Token) غير صحيح! يرجى التحقق من المراقب في القاعة.";
        
        // تسجيل محاولة الدخول الخاطئة في الـ LOGS لدواعي أمنية
        $failed_log = "محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: " . $ip_address;
        $conn->query("INSERT INTO logs (user_id, action) VALUES ('".$_SESSION['user_id']."', '$failed_log')");
    }
}
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-6 col-lg-5">
        
        <div class="card card-custom p-4 bg-white text-center mb-4">
            <div class="mb-3 text-primary">
                <i class="fa-solid fa-lock-open fa-3x text-warning"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?php echo $exam['title']; ?></h4>
            <p class="text-muted mb-3"><?php echo $exam['course_name']; ?></p>
            
            <div class="row g-2 text-center bg-light p-3 rounded mb-3">
                <div class="col-6 border-end">
                    <small class="text-muted d-block">المدة الزمنية</small>
                    <strong class="text-dark"><i class="fa-regular fa-clock me-1 text-primary"></i> <?php echo $exam['duration']; ?> دقيقة</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">الدرجة الكلية</small>
                    <strong class="text-dark"><i class="fa-solid fa-star me-1 text-warning"></i> <?php echo $exam['total_marks']; ?> درجات</strong>
                </div>
            </div>
        </div>

        <div class="card card-custom p-4 bg-white shadow-sm" style="border-top: 4px solid #003366;">
            <h5 class="fw-bold text-center mb-3 text-dark">التحقق الأمني من القاعة</h5>
            <p class="text-muted text-center" style="font-size: 14px;">يرجى إدخال الرمز الفوري الذي يمليه عليك مراقب معمل جامعة إقليم سبأ الآن لتفعيل ورقة الامتحان.</p>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger text-center p-2" style="font-size: 14px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="mb-4">
                    <input type="text" name="exam_token" class="form-control form-control-lg text-center fw-bold text-primary placeholder-glow" 
                           placeholder="أدخل الرمز هنا (مثال: USZ26)" style="letter-spacing: 4px; border-radius: 8px;" required>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-light w-50 py-2 fw-bold"><i class="fa-solid fa-arrow-right me-1"></i> تراجع</a>
                    <button type="submit" name="verify_token" class="btn btn-primary w-50 py-2 fw-bold" style="background-color: #003366;"><i class="fa-solid fa-key me-1"></i> بدء الاختبار</button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php
// استدعاء الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>