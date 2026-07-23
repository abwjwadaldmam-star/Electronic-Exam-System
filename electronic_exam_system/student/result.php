<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. تضمين ملف الاتصال بقاعدة البيانات والهيدر الموحد للجامعة
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/header.php'; 
date_default_timezone_set('Asia/Aden');

// 2. حماية الصفحة: إذا حاول الطالب الدخول مباشرة بدون وجود جلسة اختبار مكتملة يتم تحويله للوحة التحكم
if (!isset($_SESSION['last_result'])) {
    echo "<script>window.location.href='student_dashboard.php';</script>";
    exit();
}

// جلب بيانات النتيجة المحفوظة مؤقتاً في الجلسة من ملف save_answer.php
$res = $_SESSION['last_result'];
$exam_title = $res['exam_title'];
$total_score = intval($res['total_score']);
$max_marks = intval($res['max_marks']);
$start_time_formatted = date('h:i A', strtotime($res['start_time']));
$end_time_formatted = date('h:i A', strtotime($res['end_time']));

// 3. تحديد الحالة الأكاديمية (ناجح إذا حصل على 50% أو أكثر من الدرجة الكلية)
$passing_mark = $max_marks > 0 ? ($max_marks / 2) : 0;
if ($total_score >= $passing_mark) {
    $status_text = "ناجح";
    $status_class = "status-success";
} else {
    $status_text = "راسب";
    $status_class = "status-danger";
}
?>

<style>
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .result-container {
        max-width: 620px;
        margin: 50px auto;
        padding: 10px;
    }
    .result-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        padding: 40px 30px;
        text-align: center;
    }
    .icon-wrapper {
        width: 72px;
        height: 72px;
        background-color: #e6f7ed;
        color: #28a745;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px auto;
    }
    .icon-wrapper svg {
        width: 38px;
        height: 38px;
    }
    .main-title {
        color: #1e293b;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 8px;
    }
    .sub-title {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    .details-box {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background-color: #f8fafc;
        padding: 20px 25px;
        margin-bottom: 35px;
        text-align: right;
    }
    .info-label {
        color: #94a3b8;
        font-size: 0.85rem;
        display: block;
        margin-bottom: 4px;
        text-align: center;
    }
    .exam-name {
        color: #0f172a;
        font-weight: 700;
        font-size: 1.3rem;
        text-align: center;
        margin-bottom: 25px;
    }
    .grid-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 20px;
        margin-bottom: 15px;
    }
    .grid-item {
        flex: 1;
        text-align: center;
    }
    .grid-item:first-child {
        border-left: 1px solid #e2e8f0;
    }
    .score-text {
        font-size: 1.1rem;
        color: #334155;
        font-weight: 500;
    }
    .score-num {
        font-size: 2.4rem;
        font-weight: 800;
        color: #0284c7;
        vertical-align: middle;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 30px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 1rem;
        color: #ffffff;
        margin-top: 5px;
    }
    .status-success {
        background-color: #28a745;
    }
    .status-danger {
        background-color: #dc3545;
    }
    .time-meta {
        display: flex;
        justify-content: space-between;
        color: #64748b;
        font-size: 0.85rem;
        padding: 5px 10px 0 10px;
    }
    .time-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .btn-dashboard {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        background-color: #0c3966; /* متناسق مع هوية ألوان الجامعة */
        color: #ffffff;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1.05rem;
        text-decoration: none;
        transition: background-color 0.2s;
        border: none;
    }
    .btn-dashboard:hover {
        background-color: #092a4d;
        color: #ffffff;
    }
</style>

<div class="container result-container" dir="rtl">
    <div class="result-card">
        
        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        
        <h2 class="main-title">تم تسليم الامتحان بنجاح!</h2>
        <p class="sub-title">نشكرك عزيزي الطالب، تم حفظ وإغلاق ورقتك الاختبارية بنجاح في سيرفر الجامعة.</p>
        
        <div class="details-box">
            <span class="info-label">اسم الاختبار</span>
            <div class="exam-name"><?php echo htmlspecialchars($exam_title); ?></div>
            
            <div class="grid-info">
                <div class="grid-item">
                    <span class="info-label">الدرجة المحصلة</span>
                    <div class="score-text">
                        <span class="score-num"><?php echo $total_score; ?></span> / <?php echo $max_marks; ?>
                    </div>
                </div>
                <div class="grid-item">
                    <span class="info-label">الحالة الأكاديمية</span>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            </div>
            
            <div class="time-meta">
                <span>🕒 بدء الاختبار: <?php echo $start_time_formatted; ?></span>
                <span>🏁 تسليم الورقة: <?php echo $end_time_formatted; ?></span>
            </div>
        </div>
        
        <a href="student_dashboard.php" class="btn btn-dashboard shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
              <path d="M6.5 14.5v-3.507c0-.157.126-.284.284-.284h2.432c.158 0 .284.127.284.284v3.507c0 .157-.126.284-.284.284H6.784a.284.284 0 0 1-.284-.284z"/>
              <path d="M2.057 6.9a.5.5 0 0 1 .686-.172L8 11.182l5.257-4.454a.5.5 0 1 1 .686.733L8 12.074 2.229 7.46a.5.5 0 0 1-.172-.686z"/>
              <path d="M13.5 5.5v7a1.5 1.5 0 0 1-1.5 1.5H4a1.5 1.5 0 0 1-1.5-1.5v-7l5.5-4.5 5.5 4.5z"/>
            </svg>
            العودة للوحة التحكم الرئيسية
        </a>

    </div>
</div>

<?php 
// 4. تنظيف جلسة النتيجة المحددة فوراً بعد العرض لمنع استدعائها مرة أخرى بالخطأ عند التصفح المباشر
unset($_SESSION['last_result']);

include_once __DIR__ . '/../includes/footer.php'; 
?>