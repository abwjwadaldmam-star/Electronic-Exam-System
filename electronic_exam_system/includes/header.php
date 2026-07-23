<?php
// تفعيل بدء الجلسة بأمان إذا لم تكن بدأت بعد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات
include_once __DIR__ . '/../includes/config.php';

// 🔥 [تحسين الأداء الحاسم]: فك قفل الجلسة فوراً بعد القراءة لمنع بطء التنقل بين الصفحات
// هذا السطر يمنع المتصفح من الانتظار (Blocking) ويجعل الانتقال بين الواجهات فورياً وسريعاً جداً
session_write_close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الامتحانات الإلكترونية | جامعة إقليم سبأ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f4f7f6;
        }
        .navbar-custom {
            background-color: #003366; /* الأزرق الملكي الخاص بالجامعة */
            border-bottom: 4px solid #D4AF37; /* الخط الذهبي الفخم */
        }
        .navbar-brand img {
            max-height: 60px; /* حجم الشعار */
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));
        }
        .nav-link-custom {
            color: #ffffff !important;
            font-weight: 500;
        }
        .nav-link-custom:hover {
            color: #D4AF37 !important;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .card-custom:hover {
            transform: translateY(-5px);
        }
    </style>
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-white gap-3" href="dashboard.php">
            <img src="/electronic_exam_system/assets/images/logo.jpeg" alt="شعار جامعة إقليم سبأ">
            <div>
                <span class="fw-bold d-block fs-5">جامعة إقليم سبأ</span>
                <small class="text-white-50" style="font-size: 11px;">نظام الامتحانات الإلكترونية المحوسب</small>
            </div>
        </a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="d-flex align-items-center gap-3 ms-auto text-white">
            <span class="d-none d-md-inline text-white-50">مرحباً بك:</span>
            <strong class="text-warning"><i class="fa-solid fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            <span class="badge bg-light text-dark px-2 py-1">
                <?php 
                // تحويل المسمى الوظيفي لعربي بشكل أرقى أمام اللجنة
                $role = $_SESSION['role'];
                if($role == 'instructor') echo 'عضو هيئة تدريس';
                elseif($role == 'admin') echo 'مدير النظام';
                elseif($role == 'head_of_dept') echo 'رئيس القسم';
                else echo ucfirst($role);
                ?>
            </span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-sign-out-alt"></i> خروج</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container">