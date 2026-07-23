<?php
// بدء الجلسة بأمان في أعلى الملف
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات
include_once 'includes/config.php';

$error = "";

if (isset($_POST['login'])) {
    $university_id = sanitize($_POST['university_id']);
    $password = sanitize($_POST['password']); 

    // استعلام فحص المستخدم
    $sql = "SELECT * FROM users WHERE university_id = '$university_id' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['university_id'] = $user['university_id'];
        $_SESSION['department'] = ""; // قيمة افتراضية للقسم

        // توجيه المستخدم حسب الصلاحية والمجلد الخاص به
        if ($user['role'] == 'student') {
            $sub = $conn->query("SELECT student_id, department FROM students WHERE user_id = ".$user['user_id']);
            if($sub && $sub->num_rows > 0) {
                $sub_data = $sub->fetch_assoc();
                $_SESSION['student_id'] = $sub_data['student_id'];
                $_SESSION['department'] = $sub_data['department'];
            }
            header("Location: student/dashboard.php");
            exit();

        } elseif ($user['role'] == 'instructor') {
            $sub = $conn->query("SELECT instructor_id, department FROM instructors WHERE user_id = ".$user['user_id']);
            if($sub && $sub->num_rows > 0) {
                $sub_data = $sub->fetch_assoc();
                $_SESSION['instructor_id'] = $sub_data['instructor_id'];
                $_SESSION['department'] = $sub_data['department'];
            }
            header("Location: instructor/dashboard.php");
            exit();

        } elseif ($user['role'] == 'head_of_dept') {
            // جلب قسم رئيس القسم لتطبيق الحماية والعزل
            $sub = $conn->query("SELECT department FROM instructors WHERE user_id = ".$user['user_id']);
            if($sub && $sub->num_rows > 0) {
                $_SESSION['department'] = $sub->fetch_assoc()['department'];
            }
            header("Location: hod/dashboard.php");
            exit();

        } elseif ($user['role'] == 'control') {
            header("Location: control/dashboard.php");
            exit();

        } elseif ($user['role'] == 'dean') {
            header("Location: dean/dashboard.php");
            exit();

        } elseif ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        }
        
    } else {
        $error = "الرقم الجامعي أو كلمة المرور غير صحيحة!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة تسجيل الدخول | جامعة إقليم سبأ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #f4f7f6; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-top: 5px solid #003366; }
        .btn-primary { background-color: #003366; border: none; }
        .btn-primary:hover { background-color: #002244; }
        .brand-logo { max-height: 90px; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.1)); }
    </style>
</head>
<body>

<div class="container d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 100vh;">
    <div class="mb-4">
        <img src="assets/images/logo.jpeg" alt="شعار جامعة إقليم سبأ" class="brand-logo mb-3" onerror="this.src='https://via.placeholder.com/90x90?text=USZ+Logo'">
        <h2 class="fw-bold text-dark">جامعة إقليم سبأ</h2>
        <p class="text-muted fs-5">نظام إدارة الامتحانات الإلكترونية للمقرات والمعامل</p>
    </div>

    <div class="card login-card p-4 text-start w-100" style="max-width: 450px;">
        <h4 class="fw-bold text-center mb-4 text-dark"><i class="fa-solid fa-right-to-bracket text-primary me-2"></i>تسجيل الدخول البوابة</h4>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger text-center py-2" style="font-size: 14px;"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">الرقم الجامعي / الوظيفي</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-id-card text-muted"></i></span>
                    <input type="text" name="university_id" class="form-control" placeholder="مثال: 220101050" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary">كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="******" required>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fs-5 fw-bold rounded-pill"><i class="fa-solid fa-arrow-left-to-bracket me-2"></i>دخول النظام</button>
        </form>
    </div>
    
    <div class="mt-4 text-muted">
        <small>جميع الحقوق محفوظة لجامعة إقليم سبأ &copy; 2026</small>
    </div>
</div>

</body>
</html>