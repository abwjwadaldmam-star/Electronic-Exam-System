<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// التحقق من وجود الامتحان وجلب تفاصيله
$exam_sql = "SELECT * FROM exams WHERE exam_id = '$exam_id'";
$exam_res = $conn->query($exam_sql);
if (!$exam_res || $exam_res->num_rows == 0) {
    echo "<div class='alert alert-danger text-center mt-5'>عذراً، هذا الامتحان غير موجود أو تم حذفه!</div>";
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}
$exam_row = $exam_res->fetch_assoc();
$course_id = $exam_row['course_id']; // جلب رقم المادة لربطها بالبنك

// جلب الـ instructor_id الحقيقي للمدرس الحالي من جدول instructors
$user_id = $_SESSION['user_id'];
$instructor_id = 1; // قيمة احتياطية
$inst_sql = "SELECT instructor_id FROM instructors WHERE user_id = '$user_id'";
$inst_res = $conn->query($inst_sql);
if ($inst_res && $inst_res->num_rows > 0) {
    $inst_row = $inst_res->fetch_assoc();
    $instructor_id = $inst_row['instructor_id'];
}

// المعالجة البرمجية عند ضغط زر "حفظ السؤال"
if (isset($_POST['save_question'])) {
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $question_type = $conn->real_escape_string($_POST['question_type']);
    $marks = intval($_POST['marks']);

    // 🔥 [التحديث الجوهري]: إدخال السؤال في جدول بنك الأسئلة الجديد بحالة pending تلقائياً
    $ins_q_sql = "INSERT INTO question_bank (course_id, exam_id, instructor_id, question_text, question_type, difficulty, marks, status) 
                  VALUES ('$course_id', '$exam_id', '$instructor_id', '$question_text', '$question_type', 'medium', '$marks', 'pending')";
    
    if ($conn->query($ins_q_sql)) {
        $question_id = $conn->insert_id; // هذا الرقم يمثل الآن الترقيم في جدول بنك الأسئلة

        // 2. إذا كان السؤال اختيار من متعدد (MCQ)
        if ($question_type === 'mcq') {
            $options = isset($_POST['options']) ? $_POST['options'] : [];
            $correct_option = isset($_POST['correct_option']) ? intval($_POST['correct_option']) : 0;

            foreach ($options as $index => $option_text) {
                if (!empty($option_text)) {
                    $option_text = $conn->real_escape_string($option_text);
                    $is_correct = ($index === $correct_option) ? 1 : 0;
                    
                    // بقيت الخيارات مرتبطة برقم السؤال الجديد عبر حقل question_id (تأكد أن جدول choices مرتبط بـ question_bank أو عدله لاحقاً)
                    $conn->query("INSERT INTO choices (question_id, choice_text, is_correct) VALUES ('$question_id', '$option_text', '$is_correct')");
                }
            }
        }
        // 3. إذا كان السؤال صح أو خطأ (True/False)
        elseif ($question_type === 'truefalse') {
            $correct_tf = $conn->real_escape_string($_POST['correct_tf']);
            
            $tf_options = ['صح', 'خطأ'];
            foreach ($tf_options as $option_text) {
                $is_correct = ($option_text === $correct_tf) ? 1 : 0;
                $conn->query("INSERT INTO choices (question_id, choice_text, is_correct) VALUES ('$question_id', '$option_text', '$is_correct')");
            }
        }

        // 🔥 إعادة حساب المجموع الكلي للأسئلة المضافة حالياً من جدول البنك وتحديث جدول الامتحانات
        $sum_res = $conn->query("SELECT SUM(marks) as total_sum FROM question_bank WHERE exam_id = '$exam_id'");
        if ($sum_res) {
            $sum_row = $sum_res->fetch_assoc();
            $new_total_marks = isset($sum_row['total_sum']) ? intval($sum_row['total_sum']) : 0;
            $conn->query("UPDATE exams SET total_marks = '$new_total_marks' WHERE exam_id = '$exam_id'");
        }

        echo "<script>alert('تم إضافة السؤال إلى بنك الأسئلة بنجاح وهو بانتظار مراجعة رئيس القسم الآن!'); window.location.href='add_questions.php?exam_id=$exam_id';</script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>حدث خطأ أثناء حفظ السؤال: " . $conn->error . "</div>";
    }
}

// 🔥 [التحديث الجوهري]: جلب الأسئلة المضافة مسبقاً من جدول بنك الأسئلة لقراءة البيانات حية
$questions_sql = "SELECT * FROM question_bank WHERE exam_id = '$exam_id' ORDER BY question_id DESC";
$questions_res = $conn->query($questions_sql);

// جلب المجموع الحالي للدرجات من جدول البنك
$current_total = 0;
$total_check = $conn->query("SELECT SUM(marks) as current_sum FROM question_bank WHERE exam_id = '$exam_id'");
if ($total_check) {
    $t_row = $total_check->fetch_assoc();
    $current_total = isset($t_row['current_sum']) ? intval($t_row['current_sum']) : 0;
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill btn-sm mb-2"><i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة التحكم</a>
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-folder-plus text-primary me-2"></i> إدارة وبناء أسئلة: <?php echo htmlspecialchars($exam_row['title']); ?></h3>
        </div>
        <div class="bg-primary text-white px-4 py-2 rounded-4 shadow-sm text-center" style="background-color: #003366 !important;">
            <span class="small d-block text-white-50">إجمالي درجة الامتحان المقترحة</span>
            <span class="fs-4 fw-bold"><?php echo $current_total; ?> درجة</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="fa-solid fa-plus-circle me-1"></i> إضافة سؤال جديد للبنك</h5>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نص السؤال الأكاديمي</label>
                        <textarea name="question_text" class="form-control rounded-3" rows="3" placeholder="اكتب السؤال هنا..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">نوع السؤال</label>
                            <select name="question_type" id="question_type" class="form-select rounded-3" onchange="toggleQuestionFields()" required>
                                <option value="mcq">اختيار من متعدد (MCQ)</option>
                                <option value="truefalse">صح / خطأ</option>
                                <option value="essay">مقالي (Essay)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">درجة السؤال</label>
                            <input type="number" name="marks" class="form-control rounded-3" min="1" value="1" required>
                        </div>
                    </div>

                    <div id="mcq_fields" class="mb-4">
                        <label class="form-label fw-semibold text-success d-block mb-2">الخيارات الأربعة (حدد الإجابة الصحيحة):</label>
                        <?php for($i = 0; $i < 4; $i++): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light">
                                    <input type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo $i===0?'checked':''; ?> class="form-check-input mt-0">
                                </span>
                                <input type="text" name="options[]" class="form-control mcq-input" placeholder="الخيار رقم <?php echo $i+1; ?>" <?php echo $i < 2 ? 'required' : ''; ?>>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div id="tf_fields" class="mb-4" style="display: none;">
                        <label class="form-label fw-semibold text-success d-block mb-2">الإجابة الصحيحة:</label>
                        <div class="form-check form-check-inline ms-3">
                            <input class="form-check-input" type="radio" name="correct_tf" id="tf_t" value="صح" checked>
                            <label class="form-check-label px-2" for="tf_t">صح</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="correct_tf" id="tf_f" value="خطأ">
                            <label class="form-check-label px-2" for="tf_f">خطأ</label>
                        </div>
                    </div>

                    <div id="essay_fields" class="mb-4 alert alert-info py-2" style="display: none;">
                        <i class="fa-solid fa-circle-info me-1"></i> الأسئلة المقالية تمنح الطالب صندوق نص مفتوح ليكتب إجابته بشكل حر.
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="save_question" class="btn btn-success rounded-pill fw-bold py-2" style="background-color: #28a745; border: none;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ وإرسال السؤال للمراجعة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark mb-4 border-bottom pb-2"><i class="fa-solid fa-list-ol me-1"></i> الأسئلة المقترحة بانتظار المراجعة (<?php echo ($questions_res)?$questions_res->num_rows:0; ?>)</h5>
                
                <div class="accordion" id="questionsAccordion">
                    <?php if ($questions_res && $questions_res->num_rows > 0): ?>
                        <?php while($q = $questions_res->fetch_assoc()): $curr_q_id = $q['question_id']; ?>
                            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                                <div class="accordion-header" id="heading_<?php echo $curr_q_id; ?>">
                                    <div class="bg-light text-dark fw-bold d-flex justify-content-between align-items-center p-3">
                                        
                                        <div class="d-flex justify-content-between align-items-center flex-grow-1 px-2" 
                                             data-bs-toggle="collapse" 
                                             data-bs-target="#collapse_<?php echo $curr_q_id; ?>" 
                                             aria-expanded="false" 
                                             aria-controls="collapse_<?php echo $curr_q_id; ?>"
                                             style="cursor: pointer; user-select: none;">
                                            <div>
                                                <i class="fa-solid fa-chevron-down me-2 text-muted small"></i> 
                                                <span>سؤال: <?php echo htmlspecialchars($q['question_text']); ?></span>
                                                <?php if($q['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark small ms-2" style="font-size: 10px;">قيد الانتظار</span>
                                                <?php elseif($q['status'] == 'approved'): ?>
                                                    <span class="badge bg-success small ms-2" style="font-size: 10px;">معتمد</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger small ms-2" style="font-size: 10px;">مستبعد</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-primary rounded-pill ms-2"><?php echo $q['marks']; ?> درجات</span>
                                        </div>
                                        
                                        <div class="d-flex gap-1" style="position: relative; z-index: 5;">
                                            <a href="edit_question.php?question_id=<?php echo $curr_q_id; ?>&exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-warning text-dark fw-bold py-1 px-2 rounded-2" style="font-size:12px;">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="delete_question.php?question_id=<?php echo $curr_q_id; ?>&exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-danger py-1 px-2 rounded-2" style="font-size:12px;" onclick="return confirm('هل أنت متأكد من حذف هذا السؤال من البنك؟')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                                
                                <div id="collapse_<?php echo $curr_q_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?php echo $curr_q_id; ?>" data-bs-parent="#questionsAccordion">
                                    <div class="card-body bg-white p-3 border-top">
                                        <p class="text-muted small mb-2">نوع السؤال: <strong class="text-secondary"><?php echo $q['question_type'] === 'mcq' ? 'اختيار من متعدد' : ($q['question_type'] === 'truefalse' ? 'صح / خطأ' : 'مقالي'); ?></strong></p>
                                        
                                        <?php 
                                        $choices_res = $conn->query("SELECT * FROM choices WHERE question_id = '$curr_q_id'");
                                        if ($choices_res && $choices_res->num_rows > 0):
                                        ?>
                                            <ul class="list-group list-group-flush mb-0">
                                                <?php while($c = $choices_res->fetch_assoc()): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-0 <?php echo $c['is_correct']?'bg-success bg-opacity-10 rounded text-success fw-bold':''; ?>">
                                                        <span><i class="fa-regular <?php echo $c['is_correct']?'fa-circle-check':'fa-circle'; ?> me-2"></i> <?php echo htmlspecialchars($c['choice_text']); ?></span>
                                                        <?php if($c['is_correct']): ?> <span class="badge bg-success small">الإجابة الصحيحة</span> <?php endif; ?>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clipboard-question fa-3x text-light-subtle mb-3"></i>
                            <p class="mb-0">لا توجد أسئلة مرفوعة للبنك لهذا الامتحان بعد. استخدم النموذج لإضافة أول سؤال!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleQuestionFields() {
    const type = document.getElementById('question_type').value;
    const mcqFields = document.getElementById('mcq_fields');
    const tfFields = document.getElementById('tf_fields');
    const essayFields = document.getElementById('essay_fields');
    const mcqInputs = document.querySelectorAll('.mcq-input');

    if (type === 'mcq') {
        mcqFields.style.display = 'block';
        tfFields.style.display = 'none';
        essayFields.style.display = 'none';
        
        mcqInputs.forEach((input, index) => {
            if (index < 2) input.setAttribute('required', 'required');
        });
    } else if (type === 'truefalse') {
        mcqFields.style.display = 'none';
        tfFields.style.display = 'block';
        essayFields.style.display = 'none';
        
        mcqInputs.forEach(input => input.removeAttribute('required'));
    } else if (type === 'essay') {
        mcqFields.style.display = 'none';
        tfFields.style.display = 'none';
        essayFields.style.display = 'block';
        
        mcqInputs.forEach(input => input.removeAttribute('required'));
    }
}

document.addEventListener("DOMContentLoaded", function() {
    toggleQuestionFields();
});
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>