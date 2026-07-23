<?php
// استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// قراءة المتغيرات وتأمينها
$question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// 🔥 [تعديل حاسم]: تحويل اسم الجدول من questions إلى question_bank ليتوافق مع قاعدة بياناتك الحقيقية
$q_sql = "SELECT * FROM question_bank WHERE question_id = '$question_id' AND exam_id = '$exam_id'";
$q_res = $conn->query($q_sql);

if (!$q_res || $q_res->num_rows == 0) {
    echo "<div class='alert alert-danger text-center mt-5'>عذراً، هذا السؤال غير موجود في بنك الأسئلة الحالي!</div>";
    include_once __DIR__ . '/../includes/footer.php';
    exit();
}
$question = $q_res->fetch_assoc();

// معالجة البيانات عند الضغط على "تحديث السؤال"
if (isset($_POST['update_question'])) {
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $marks = intval($_POST['marks']);
    
    // 🔥 [تعديل حاسم]: تحديث جدول question_bank بدلاً من questions
    $update_sql = "UPDATE question_bank SET question_text = '$question_text', marks = '$marks' WHERE question_id = '$question_id'";
    
    if ($conn->query($update_sql)) {
        
        // إذا كان السؤال اختيار من متعدد (MCQ) نقوم بتحديث الخيارات
        if ($question['question_type'] === 'mcq' && isset($_POST['choices'])) {
            $correct_choice_id = isset($_POST['correct_choice']) ? intval($_POST['correct_choice']) : 0;
            
            foreach ($_POST['choices'] as $c_id => $c_text) {
                $c_text = $conn->real_escape_string($c_text);
                $is_correct = ($c_id == $correct_choice_id) ? 1 : 0;
                
                $conn->query("UPDATE choices SET choice_text = '$c_text', is_correct = '$is_correct' WHERE choice_id = '$c_id' AND question_id = '$question_id'");
            }
        }
        // إذا كان السؤال صح أو خطأ
        elseif ($question['question_type'] === 'truefalse' && isset($_POST['correct_tf'])) {
            $correct_tf = $conn->real_escape_string($_POST['correct_tf']);
            
            // تحديث الخيارين بناءً على الاختيار الجديد
            $conn->query("UPDATE choices SET is_correct = (choice_text = '$correct_tf') WHERE question_id = '$question_id'");
        }

        echo "<script>alert('تم تحديث السؤال بنجاح!'); window.location.href='add_questions.php?exam_id=$exam_id';</script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>حدث خطأ أثناء التحديث: " . $conn->error . "</div>";
    }
}

// جلب الخيارات الحالية للسؤال من جدول الخيارات
$choices_res = $conn->query("SELECT * FROM choices WHERE question_id = '$question_id'");
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="add_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary rounded-pill btn-sm mb-3">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة لإدارة الأسئلة
            </a>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h4 class="fw-bold text-dark mb-4 border-bottom pb-2">
                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i> تعديل السؤال الحالي
                </h4>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نص السؤال</label>
                        <textarea name="question_text" class="form-control rounded-3" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">نوع السؤال</label>
                            <input type="text" class="form-control bg-light rounded-3 text-secondary" value="<?php echo $question['question_type'] === 'mcq' ? 'اختيار من متعدد' : ($question['question_type'] === 'truefalse' ? 'صح / خطأ' : 'مقالي'); ?>" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary">درجة السؤال</label>
                            <input type="number" name="marks" class="form-control rounded-3" min="1" value="<?php echo $question['marks']; ?>" required>
                        </div>
                    </div>

                    <?php if ($question['question_type'] === 'mcq'): ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-success d-block mb-2">الخيارات المتاحة (حدد الإجابة الصحيحة الجديدة):</label>
                            <?php while($choice = $choices_res->fetch_assoc()): ?>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-light">
                                        <input type="radio" name="correct_choice" value="<?php echo $choice['choice_id']; ?>" <?php echo $choice['is_correct'] ? 'checked' : ''; ?> class="form-check-input mt-0">
                                    </span>
                                    <input type="text" name="choices[<?php echo $choice['choice_id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($choice['choice_text']); ?>" required>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($question['question_type'] === 'truefalse'): 
                        // جلب الخيار الصحيح الحالي لمعرفته
                        $current_correct = '';
                        while($c = $choices_res->fetch_assoc()) {
                            if($c['is_correct']) $current_correct = $c['choice_text'];
                        }
                    ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-success d-block mb-2">الإجابة الصحيحة الحالية:</label>
                            <div class="form-check form-check-inline ms-3">
                                <input class="form-check-input" type="radio" name="correct_tf" id="edit_tf_t" value="صح" <?php echo $current_correct === 'صح' ? 'checked' : ''; ?>>
                                <label class="form-check-label px-2" for="edit_tf_t">صح</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="correct_tf" id="edit_tf_f" value="خطأ" <?php echo $current_correct === 'خطأ' ? 'checked' : ''; ?>>
                                <label class="form-check-label px-2" for="edit_tf_f">خطأ</label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($question['question_type'] === 'essay'): ?>
                        <div class="mb-4 alert alert-info py-2">
                            <i class="fa-solid fa-circle-info me-1"></i> هذا السؤال مقالي، لا يحتوي على خيارات مسبقة الإعداد.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="add_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-light rounded-pill px-4">إلغاء</a>
                        <button type="submit" name="update_question" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ التعديلات الجديدة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// استدعاء ملف الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>