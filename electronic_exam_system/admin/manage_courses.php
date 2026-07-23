<?php
// البدء بالجلسة واستدعاء الهيدر المشترك
ob_start();
include_once __DIR__ . '/../includes/header.php';

// حماية الصفحة: السماح لمدير النظام (admin) فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 🟢 أولاً: معالجة إضافة مقرر دراسي جديد عند إرسال النموذج
if (isset($_POST['add_course'])) {
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $course_code = $conn->real_escape_string($_POST['course_code']);
    $instructor_id = intval($_POST['instructor_id']);

    // التحقق من عدم تكرار كود المادة
    $check_code = $conn->query("SELECT course_id FROM courses WHERE course_code = '$course_code'");
    if ($check_code && $check_code->num_rows > 0) {
        echo "<script>alert('❌ كود المقرر هذا مسجل مسبقاً لمادة أخرى!');</script>";
    } else {
        // إدخال المادة الجديدة وربطها بالمدرس المحدد
        $insert_course_sql = "INSERT INTO courses (course_name, course_code, instructor_id) 
                              VALUES ('$course_name', '$course_code', '$instructor_id')";
        
        if ($conn->query($insert_course_sql)) {
            echo "<script>
                alert('✅ تم إضافة المقرر الدراسي وإسناده للمدرس بنجاح!');
                window.location.href = 'manage_courses.php';
            </script>";
            exit();
        } else {
            echo "<div class='alert alert-danger text-center m-3'>حدث خطأ أثناء إضافة المادة: " . $conn->error . "</div>";
        }
    }
}

// 🔴 ثانياً: معالجة حذف المقرر الدراسي
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // حذف المادة (تنبيه: سيؤدي هذا برمجياً لحذف امتحاناتها تتبعاً أو فصلها حسب قيود قاعدة البيانات لديك)
    $conn->query("DELETE FROM courses WHERE course_id = '$delete_id'");
    
    header("Location: manage_courses.php");
    exit();
}

// 🔵 ثالثاً: جلب قائمة المدرسين المتوفرين لتغذية قائمة الاختيار (Dropdown)
$instructors_list = $conn->query("
    SELECT i.instructor_id, u.full_name 
    FROM instructors i 
    INNER JOIN users u ON i.user_id = u.user_id 
    ORDER BY u.full_name ASC
");

// 🟡 رابعاً: جلب قائمة المقررات الحالية مع اسم المدرس المسؤول عنها لعرضها أمام اللجنة
$courses_res = $conn->query("
    SELECT c.*, u.full_name AS instructor_name 
    FROM courses c 
    LEFT JOIN instructors i ON c.instructor_id = i.instructor_id 
    LEFT JOIN users u ON i.user_id = u.user_id 
    ORDER BY c.course_id DESC
");
?>

<div class="container-fluid px-4 mt-4">
    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill btn-sm mb-3">
        <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة التحكم الرئيسية
    </a>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="text-center mb-4">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block text-success mb-2">
                        <i class="fa-solid fa-book-medical fa-xl"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">إضافة مقرر جديد</h5>
                    <p class="text-muted small">تهيئة مادة دراسية وإسنادها لعضو التدريس</p>
                </div>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">اسم المقرر (المادة)</label>
                        <input type="text" name="course_name" class="form-control rounded-3 py-2" placeholder="مثال: هندسة البرمجيات" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">ترميز / كود المادة</label>
                        <input type="text" name="course_code" class="form-control rounded-3 py-2" placeholder="مثال: IT-202" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">عضو هيئة التدريس المسؤول</label>
                        <select name="instructor_id" class="form-select rounded-3 py-2" required>
                            <option value="">-- اختر المدرس المسؤول عن المادة --</option>
                            <?php 
                            if ($instructors_list && $instructors_list->num_rows > 0) {
                                while($inst = $instructors_list->fetch_assoc()) {
                                    echo "<option value='".$inst['instructor_id']."'>".$inst['full_name']."</option>";
                                }
                            } else {
                                echo "<option value=''>⚠️ لا يوجد مدرسين مسجلين في النظام حالياً</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="add_course" class="btn btn-success rounded-pill fw-bold py-2" style="background-color: #198754; border: none;">
                            <i class="fa-solid fa-plus-circle me-1"></i> إدراج المقرر في الخطة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-graduation-cap text-success me-2"></i> المقررات والمواد المعتمدة في المنظومة
                    </h5>
                    <span class="badge bg-light text-dark fs-6 border">إجمالي المواد: <?php echo $courses_res ? $courses_res->num_rows : 0; ?></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border text-center mb-0">
                        <thead class="table-light fw-bold">
                            <tr>
                                <th class="py-3">معرف المادة</th>
                                <th class="py-3 text-start px-4">اسم المقرر الدراسي</th>
                                <th class="py-3">كود المقرر</th>
                                <th class="py-3">الأستاذ المسؤول</th>
                                <th class="py-3">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($courses_res && $courses_res->num_rows > 0): ?>
                                <?php while($row = $courses_res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">#<?php echo $row['course_id']; ?></td>
                                        <td class="text-start px-4fw-bold text-dark">
                                            <i class="fa-solid fa-book text-muted me-2"></i><?php echo htmlspecialchars($row['course_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-bold px-3 py-2">
                                                <?php echo htmlspecialchars($row['course_code']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-primary">
                                            <i class="fa-solid fa-user-tie small me-1"></i>
                                            <?php echo htmlspecialchars($row['instructor_name'] ?? 'غير مسند لمدرس حالياً'); ?>
                                        </td>
                                        <td>
                                            <a href="manage_courses.php?delete_id=<?php echo $row['course_id']; ?>" 
                                               onclick="return confirm('⚠️ تحذير: حذف المقرر سيحذف كافة الامتحانات التابعة له، هل تريد الاستمرار؟');" 
                                               class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">
                                                <i class="fa-solid fa-trash-can me-1"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-5 text-muted fs-5">
                                        <i class="fa-solid fa-book-open fa-3x text-light-subtle d-block mb-3"></i>
                                        لا توجد أي مقررات دراسية مضافة في المنظومة حتى الآن.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
?>