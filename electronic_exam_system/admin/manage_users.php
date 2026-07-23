<?php
// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. استدعاء ملف الاتصال بقاعدة البيانات مباشرة لتجنب تعارض الحماية
include_once __DIR__ . '/../includes/config.php';

// 2. حماية الصفحة البرمجية المتقدمة (السماح فقط للأدمن بإدارة حسابات النظام)
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if (!isset($_SESSION['user_id']) || $user_role !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 3. معالجة استقبال البيانات وحفظها في قاعدة البيانات عند الضغط على زر الإضافة
if (isset($_POST['add_user'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $university_id = $conn->real_escape_string($_POST['university_id']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']); 
    $role = $conn->real_escape_string($_POST['role']);

    // إدخال البيانات في الجدول الرئيسي (users) بالصلاحية الجديدة
    $insert_user_query = "INSERT INTO users (full_name, university_id, email, password, role, created_at) 
                          VALUES ('$full_name', '$university_id', '$email', '$password', '$role', NOW())";
    
    if ($conn->query($insert_user_query)) {
        $new_user_id = $conn->insert_id;

        // التحقق من الأدوار لحقن الجداول الفرعية بالبيانات الأكاديمية المطلوبة دون قيم فارغة
        if ($role === 'instructor' || $role === 'head_of_dept') {
            $academic_rank = !empty($_POST['academic_rank']) ? $conn->real_escape_string($_POST['academic_rank']) : 'عضو هيئة تدريس';
            $department = !empty($_POST['department']) ? $conn->real_escape_string($_POST['department']) : 'تكنولوجيا المعلومات';
            
            $conn->query("INSERT INTO instructors (user_id, academic_rank, department) VALUES ('$new_user_id', '$academic_rank', '$department')");
        
        } elseif ($role === 'student') {
            $level = !empty($_POST['level']) ? $conn->real_escape_string($_POST['level']) : '4';
            $department = !empty($_POST['student_department']) ? $conn->real_escape_string($_POST['student_department']) : 'علوم حاسوب';
            
            $conn->query("INSERT INTO students (user_id, level, department) VALUES ('$new_user_id', '$level', '$department')");
        }
        
        echo "<script>alert('تم إضافة الحساب بنجاح وتخصيص الصلاحيات الأكاديمية المتطورة له!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء إضافة المستخدم: " . $conn->error . "');</script>";
    }
}

// 4. استدعاء ملف الهيدر المشترك
include_once __DIR__ . '/../includes/header.php';
?>

<style>
    .modern-card {
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
        background: #ffffff;
    }
    .modern-header {
        background: linear-gradient(135deg, #003366 0%, #002244 100%) !important;
        border-bottom: 4px solid #D4AF37 !important;
        border-top-left-radius: 16px !important;
        border-top-right-radius: 16px !important;
        padding: 1.5rem !important;
    }
    .form-label-modern {
        font-weight: 600;
        color: #4a5568;
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    .form-label-modern i {
        margin-left: 6px;
    }
    .form-control-modern {
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 0.65rem 1rem !important;
        background-color: #f8fafc !important;
        transition: all 0.2s ease-in-out !important;
    }
    .form-control-modern:focus {
        background-color: #ffffff !important;
        border-color: #003366 !important;
        box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.15) !important;
    }
    .dynamic-box-instructor {
        border-right: 4px solid #003366 !important;
        background-color: #f0f7ff !important;
        border-radius: 12px;
    }
    .dynamic-box-student {
        border-right: 4px solid #ffc107 !important;
        background-color: #fffbeb !important;
        border-radius: 12px;
    }
    .btn-submit-modern {
        background: linear-gradient(135deg, #003366 0%, #002244 100%) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        padding: 0.75rem 2rem !important;
        border-radius: 30px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2) !important;
        transition: all 0.3s ease !important;
    }
    .btn-submit-modern:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(0, 51, 102, 0.3) !important;
    }
</style>

<div class="card modern-card mb-5" dir="rtl">
    <div class="card-header modern-header">
        <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-user-shield text-warning me-2"></i>إدارة وصلاحيات المستخدمين المتقدمة (RBAC)</h5>
    </div>
    
    <div class="card-body p-4 p-md-5">
        <form action="" method="POST">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label-modern"><i class="fa-solid fa-user text-primary"></i>الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control form-control-modern" placeholder="ادخل اسم المستخدم الثلاثي أو الرباعي" required>
                </div>
                
                <div class="col-md-6 mb-4">
                    <label class="form-label-modern"><i class="fa-solid fa-id-card text-primary"></i>الرقم الأكاديمي / الجامعي</label>
                    <input type="text" name="university_id" class="form-control form-control-modern" placeholder="مثال: 220101050 أو inst_2026" required>
                </div>

                <div class="col-md-4 mb-4">
                    <label class="form-label-modern"><i class="fa-solid fa-envelope text-primary"></i>البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control form-control-modern" placeholder="username@usz.edu.ye" required>
                </div>

                <div class="col-md-4 mb-4">
                    <label class="form-label-modern"><i class="fa-solid fa-lock text-primary"></i>كلمة المرور</label>
                    <input type="password" name="password" class="form-control form-control-modern" placeholder="******" required>
                </div>

                <div class="col-md-4 mb-4">
                    <label class="form-label-modern"><i class="fa-solid fa-user-gear text-danger"></i>نوع الصلاحية والنطاق (Role)</label>
                    <select name="role" id="userRoleSelect" class="form-select form-control-modern fw-bold text-primary" onchange="toggleRoleFields()" required>
                        <option value="">-- اختر صلاحية الحساب --</option>
                        <option value="student">طالب (Student)</option>
                        <option value="instructor">مدرس / دكتور (Instructor)</option>
                        <option value="head_of_dept">رئيس قسم (HOD)</option>
                        <option value="control">أعضاء الكنترول (Control)</option>
                        <option value="dean">عميد الكلية (Dean)</option>
                        <option value="admin">مدير النظام (Admin)</option>
                    </select>
                </div>
            </div>

            <div id="instructorFields" class="row p-4 mb-4 dynamic-box-instructor border shadow-sm my-2" style="display: none;">
                <div class="col-12 mb-3"><h6 class="fw-bold text-primary"><i class="fa-solid fa-graduation-cap me-1"></i>البيانات الوظيفية والأكاديمية للرتبة:</h6></div>
                <div class="col-md-6 mb-2">
                    <label class="form-label-modern small">الرتبة الأكاديمية</label>
                    <select name="academic_rank" class="form-select form-control-modern">
                        <option value="رئيس قسم">رئيس قسم الأكاديمي</option>
                        <option value="أستاذ مشارك">أستاذ مشارك</option>
                        <option value="أستاذ مساعد">أستاذ مساعد</option>
                        <option value="عضو هيئة تدريس" selected>عضو هيئة تدريس</option>
                        <option value="معيد">معيد</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label-modern small">القسم التابع له النطاق</label>
                    <select name="department" class="form-select form-control-modern">
                        <option value="تكنولوجيا المعلومات" selected>تكنولوجيا المعلومات</option>
                        <option value="علوم حاسوب">علوم حاسوب</option>
                        <option value="نظم معلومات">نظم معلومات</option>
                        <option value="ذكاء اصطناعي">ذكاء اصطناعي</option>
                    </select>
                </div>
            </div>

            <div id="studentFields" class="row p-4 mb-4 dynamic-box-student border shadow-sm my-2" style="display: none;">
                <div class="col-12 mb-3"><h6 class="fw-bold text-warning"><i class="fa-solid fa-user-graduate me-1"></i>البيانات التعليمية للطالب:</h6></div>
                <div class="col-md-6 mb-2">
                    <label class="form-label-modern small">الالتحاق بالقسم</label>
                    <select name="student_department" class="form-select form-control-modern">
                        <option value="علوم حاسوب" selected>علوم حاسوب</option>
                        <option value="تكنولوجيا المعلومات">تكنولوجيا المعلومات</option>
                        <option value="نظم معلومات">نظم معلومات</option>
                        <option value="ذكاء اصطناعي">ذكاء اصطناعي</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label-modern small">المستوى الدراسي الحالي</label>
                    <select name="level" class="form-select form-control-modern">
                        <option value="1">المستوى الأول</option>
                        <option value="2">المستوى الثاني</option>
                        <option value="3">المستوى الثالث</option>
                        <option value="4" selected>المستوى الرابع</option>
                    </select>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" name="add_user" class="btn btn-submit-modern px-4">
                    <i class="fa-solid fa-floppy-disk me-2 text-warning"></i> حفظ الحساب وتفعيل الصلاحية
                </button>
            </div>
        </form>
    </div>
</div>


<div class="card modern-card mb-4" dir="rtl">
    <div class="card-header bg-light p-4 border-bottom d-flex justify-content-between align-items-center" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>هيكلية الحسابات المفعلة بالنظام</h5>
        <span class="badge bg-success px-3 py-2 rounded-pill fs-6"><i class="fa-solid fa-circle-check me-1"></i> جلب حيا ومؤمن برمجيا</span>
    </div>
    
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light text-secondary fw-bold">
                    <tr>
                        <th style="width: 80px;">رقم الحساب</th>
                        <th>الاسم الكامل</th>
                        <th>الرقم الجامعي / الأكاديمي</th>
                        <th>البريد الإلكتروني</th>
                        <th>نوع الحساب (الدور)</th>
                        <th>نطاق التخصص والقيود</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // استعلام موحد يربط الجداول لجلب كافة التفاصيل والأدوار الجديدة حياً
                    $fetch_users_sql = "
                        SELECT u.user_id, u.full_name, u.university_id, u.email, u.role,
                               s.department AS student_dept, s.level AS student_level,
                               i.department AS inst_dept, i.academic_rank
                        FROM users u
                        LEFT JOIN students s ON u.user_id = s.user_id
                        LEFT JOIN instructors i ON u.user_id = i.user_id
                        ORDER BY u.user_id DESC
                    ";
                    $users_result = $conn->query($fetch_users_sql);

                    if ($users_result && $users_result->num_rows > 0):
                        while($row = $users_result->fetch_assoc()):
                            
                            $role_badge = '';
                            $special_info = '<span class="text-muted small">لا توجد قيود/نطاق عام</span>';
                            
                            switch ($row['role']) {
                                case 'admin':
                                    $role_badge = '<span class="badge bg-danger py-2 px-3 rounded-pill fw-bold">مدير نظام</span>';
                                    $special_info = '<span class="text-danger fw-semibold small"><i class="fa-solid fa-key me-1"></i> صلاحيات مطلقة</span>';
                                    break;
                                case 'dean':
                                    $role_badge = '<span class="badge bg-dark text-white py-2 px-3 rounded-pill fw-bold">عميد الكلية</span>';
                                    $special_info = '<span class="text-dark fw-semibold small"><i class="fa-solid fa-chart-line me-1"></i> تقارير الكلية كاملة</span>';
                                    break;
                                case 'head_of_dept':
                                    $role_badge = '<span class="badge bg-info text-dark py-2 px-3 rounded-pill fw-bold">رئيس قسم</span>';
                                    $dept = !empty($row['inst_dept']) ? $row['inst_dept'] : 'تكنولوجيا المعلومات';
                                    $special_info = "<div class='small text-primary fw-bold'><strong>إدارة قسم:</strong> $dept</div>";
                                    break;
                                case 'instructor':
                                    $role_badge = '<span class="badge bg-primary py-2 px-3 rounded-pill fw-bold">دكتور / مدرس</span>';
                                    $dept = !empty($row['inst_dept']) ? $row['inst_dept'] : 'تكنولوجيا المعلومات';
                                    $rank = !empty($row['academic_rank']) ? $row['academic_rank'] : 'عضو هيئة تدريس';
                                    $special_info = "<div class='small text-dark'><strong>الرتبة:</strong> $rank</div><div class='small text-muted'><strong>القسم:</strong> $dept</div>";
                                    break;
                                case 'control':
                                    $role_badge = '<span class="badge bg-secondary py-2 px-3 rounded-pill fw-bold">الكنترول</span>';
                                    $special_info = '<span class="text-secondary fw-semibold small"><i class="fa-solid fa-print me-1"></i> رصد وطباعة الكشوفات</span>';
                                    break;
                                case 'student':
                                    $role_badge = '<span class="badge bg-warning text-dark py-2 px-3 rounded-pill fw-bold">طالب</span>';
                                    $s_dept = !empty($row['student_dept']) ? $row['student_dept'] : 'علوم حاسوب';
                                    $s_level = !empty($row['student_level']) ? $row['student_level'] : '4';
                                    $special_info = "<div class='small text-dark'><strong>التخصص:</strong> $s_dept</div><div class='small text-muted'><strong>المستوى:</strong> $s_level</div>";
                                    break;
                            }
                    ?>
                            <tr>
                                <td class="fw-bold text-center bg-light">#<?php echo $row['user_id']; ?></td>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div></td>
                                <td><code class="fs-6 fw-bold text-secondary"><?php echo htmlspecialchars($row['university_id']); ?></code></td>
                                <td><span class="text-muted"><?php echo htmlspecialchars($row['email']); ?></span></td>
                                <td><?php echo $role_badge; ?></td>
                                <td><?php echo $special_info; ?></td>
                            </tr>
                    <?php 
                        endwhile;
                    else:
                        echo '<tr><td colspan="6" class="text-center py-4 text-muted">لا يوجد مستخدمون مسجلون في قاعدة البيانات حالياً.</td></tr>';
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleRoleFields() {
    var role = document.getElementById('userRoleSelect').value;
    var instructorDiv = document.getElementById('instructorFields');
    var studentDiv = document.getElementById('studentFields');

    // يظهر صندوق المدرسين في حالة اختيار مدرس أو رئيس قسم لأن كلاهما يملك رتبة وقسم
    if (role === 'instructor' || role === 'head_of_dept') {
        instructorDiv.style.display = 'flex';
        studentDiv.style.display = 'none';
    } else if (role === 'student') {
        instructorDiv.style.display = 'none';
        studentDiv.style.display = 'flex';
    } else {
        instructorDiv.style.display = 'none';
        studentDiv.style.display = 'none';
    }
}
</script>

<?php
// استدعاء الفوتر المشترك
include_once __DIR__ . '/../includes/footer.php';
?>