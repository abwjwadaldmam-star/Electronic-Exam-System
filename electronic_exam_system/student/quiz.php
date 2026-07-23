<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// التحقق من الهوية وصلاحيات الطالب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); 
    exit();
}

// استدعاء ملف الهيدر المشترك (والذي يحتوي بداخلة غالباً على ملف الاتصال $conn)
include_once __DIR__ . '/../includes/header.php';

// إذا كان الهيدر لا يتضمن ملف الاتصال، نقوم بجلبه بالمسار الصحيح المباشر:
if (!isset($conn)) {
    include_once __DIR__ . '/../config/db_connection.php'; // أو المسار الفعلي للملف لديك
}

date_default_timezone_set('Asia/Aden');

$student_id = intval($_SESSION['student_id'] ?? $_SESSION['user_id']); 
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id === 0) {
    echo "<script>window.location.href='student_dashboard.php';</script>"; 
    exit();
}

// 1. جلب بيانات الامتحان ومدة التايمر بالدقائق
$stmt = $conn->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam_row) {
    die("<div class='container mt-5 text-center'><div class='alert alert-danger'>عذراً، هذا الامتحان غير موجود حالياً!</div></div>");
}

$duration_minutes = intval($exam_row['duration']);
$duration_seconds = $duration_minutes * 60;

// 2. التحقق من محاولة الطالب الحالية في جدول student_exams
$stmt_session = $conn->prepare("SELECT student_exam_id, start_time, status FROM student_exams WHERE student_id = ? AND exam_id = ? ORDER BY student_exam_id DESC LIMIT 1");
$stmt_session->bind_param("ii", $student_id, $exam_id);
$stmt_session->execute();
$session_res = $stmt_session->get_result();

if ($session_res->num_rows == 0) {
    // إنشاء محاولة جديدة وتوثيق وقت البدء بالسيرفر
    $now_time = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '::1';
    $ins_stmt = $conn->prepare("INSERT INTO student_exams (student_id, exam_id, start_time, ip_address, status) VALUES (?, ?, ?, ?, 'active')");
    $ins_stmt->bind_param("iiss", $student_id, $exam_id, $now_time, $ip_address);
    $ins_stmt->execute();
    $student_exam_id = $conn->insert_id;
    $ins_stmt->close();
    
    $time_left = $duration_seconds;
} else {
    $data = $session_res->fetch_assoc();
    $student_exam_id = $data['student_exam_id'];
    
    // التحقق من الحالات السابقة لمنع التكرار وحلقات التوجيه
    if ($data['status'] === 'completed') {
        echo "<script>window.location.href='result.php?student_exam_id=" . $student_exam_id . "';</script>";
        exit();
    }
    
    // حساب الفارق الزمني الفعلي والوقت المتبقي بدقة
    $start_time_stamp = strtotime($data['start_time']);
    $current_time_stamp = time();
    $elapsed_seconds = $current_time_stamp - $start_time_stamp;
    $time_left = $duration_seconds - $elapsed_seconds;

    // في حال نفاد الوقت الفعلي
    if ($time_left <= 0) {
        $upd_stmt = $conn->prepare("UPDATE student_exams SET status = 'completed', end_time = NOW() WHERE student_exam_id = ?");
        $upd_stmt->bind_param("i", $student_exam_id);
        $upd_stmt->execute();
        $upd_stmt->close();
        
        ?>
        <div class="container text-center mt-5" dir="rtl">
            <div class="card p-5 shadow border-0" style="border-radius: 20px; max-width: 650px; margin: auto;">
                <div class="alert alert-danger p-4 mb-4 fw-bold fs-4" style="background-color: #fee2e2; color: #dc2626; border-radius: 12px;">
                     ❌ انتهى الوقت القانوني المحدد للاختبار!
                </div>
                <p class="text-muted fs-5 mb-4">لقد انتهت المدة الزمنية المتاحة لتقديم الإجابات. تم حفظ ورقتك وإغلاقها تلقائياً בסيرفر الجامعة.</p>
                <a href="student_dashboard.php" class="btn btn-primary btn-lg px-5 rounded-pill" style="background-color: #003366; border: none;">العودة للوحة التحكم</a>
            </div>
        </div>
        <?php
        include_once __DIR__ . '/../includes/footer.php';
        exit();
    }
}
$stmt_session->close();

// 3. جلب الأسئلة المعتمدة للامتحان الحالي
$q_stmt = $conn->prepare("SELECT * FROM question_bank WHERE exam_id = ? AND status = 'approved' ORDER BY question_id ASC");
$q_stmt->bind_param("i", $exam_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();
?>

<div class="container mt-4" dir="rtl" style="max-width: 900px; user-select: none;">
    <div class="card p-4 mb-4 d-flex flex-row justify-content-between align-items-center shadow-sm border-0" style="border-radius: 12px; background: #ffffff;">
        <h4 class="fw-bold m-0 text-dark" style="color: #003366 !important;"><i class="fa-solid fa-file-signature me-2"></i> <?php echo htmlspecialchars($exam_row['title']); ?></h4>
        <div id="countdown-timer" class="badge bg-danger fs-5 px-4 py-2 rounded-pill shadow-sm">--:--</div>
    </div>

    <form id="examForm" action="save_answer.php" method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <input type="hidden" name="student_exam_id" value="<?php echo $student_exam_id; ?>">
        <input type="hidden" name="final_submit" value="1">

        <?php $q_index = 1; while ($q = $questions->fetch_assoc()): $q_id = intval($q['question_id']); ?>
            <div class="card p-4 mb-3 text-right shadow-sm border-0" style="border-radius: 12px;">
                <h5 class="fw-bold mb-3 text-secondary">سؤال <?php echo $q_index++; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h5>
                <div class="mt-3">
                    <?php if ($q['question_type'] == 'mcq'): ?>
                        <?php for ($i=1; $i<=4; $i++): if(empty($q["choice_$i"])) continue; ?>
                            <div class="form-check text-end mb-2" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse; justify-content: flex-end;">
                                <label class="form-check-label px-2 text-dark fs-6" style="cursor: pointer;" for="q<?php echo $q_id.'_'.$i; ?>"><?php echo htmlspecialchars($q["choice_$i"]); ?></label>
                                <input class="form-check-input" type="radio" name="answers[<?php echo $q_id; ?>]" value="<?php echo $i; ?>" id="q<?php echo $q_id.'_'.$i; ?>" onchange="autoSave(<?php echo $q_id; ?>, this.value)">
                            </div>
                        <?php endfor; ?>
                    <?php else: ?>
                        <div class="form-check text-end mb-2" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse; justify-content: flex-end;">
                            <label class="form-check-label px-2 text-dark fs-6" style="cursor: pointer;" for="q<?php echo $q_id; ?>_t">صح (True)</label>
                            <input class="form-check-input" type="radio" name="answers[<?php echo $q_id; ?>]" value="true" id="q<?php echo $q_id; ?>_t" onchange="autoSave(<?php echo $q_id; ?>, 'true')">
                        </div>
                        <div class="form-check text-end mb-2" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse; justify-content: flex-end;">
                            <label class="form-check-label px-2 text-dark fs-6" style="cursor: pointer;" for="q<?php echo $q_id; ?>_f">خطأ (False)</label>
                            <input class="form-check-input" type="radio" name="answers[<?php echo $q_id; ?>]" value="false" id="q<?php echo $q_id; ?>_f" onchange="autoSave(<?php echo $q_id; ?>, 'false')">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; $q_stmt->close(); ?>
        
        <div class="text-center my-5">
            <button type="submit" class="btn btn-success btn-lg px-5 py-3 fw-bold rounded-pill shadow" style="background-color: #28a745; border: none;" onclick="clearExamStorage(); window.isSubmitting = true; return confirm('هل أنت متأكد من رغبتك في إنهاء وتسليم ورقة الامتحان النهائية؟');">إرسال وتسليم الامتحان النهائي</button>
        </div>
    </form>
</div>

<script>
window.isSubmitting = false;

function autoSave(qId, val) {
    let fd = new FormData();
    fd.append('auto_save', 1);
    fd.append('question_id', qId);
    fd.append('answer', val);
    fd.append('exam_id', <?php echo $exam_id; ?>);
    fd.append('student_exam_id', <?php echo $student_exam_id; ?>);
    fetch('save_answer.php', { method: 'POST', body: fd });
}

// قفل الـ التايمر الذكي المقاوم لتحديث الصفحة
const storageKey = "exam_timer_" + <?php echo $exam_id; ?> + "_" + <?php echo $student_id; ?>;
let serverTimeLeft = <?php echo intval($time_left); ?>;
let timeLeft = localStorage.getItem(storageKey) !== null ? Math.min(parseInt(localStorage.getItem(storageKey)), serverTimeLeft) : serverTimeLeft;

localStorage.setItem(storageKey, timeLeft);

let timerInt = setInterval(() => {
    if(timeLeft <= 0) { 
        clearInterval(timerInt); 
        clearExamStorage();
        window.isSubmitting = true;
        alert("⏱️ انتهى الوقت المخصص للامتحان، سيتم إرسال الإجابات تلقائياً لحفظ حقك الأكاديمي.");
        document.getElementById('examForm').submit(); 
        return;
    }
    localStorage.setItem(storageKey, timeLeft);
    let m = Math.floor(timeLeft / 60), s = timeLeft % 60;
    document.getElementById('countdown-timer').innerText = m + ":" + (s < 10 ? '0' : '') + s;
    timeLeft--;
}, 1000);

function clearExamStorage() { 
    localStorage.removeItem(storageKey); 
}
</script>

<?php 
include_once __DIR__ . '/../includes/footer.php'; 
?>