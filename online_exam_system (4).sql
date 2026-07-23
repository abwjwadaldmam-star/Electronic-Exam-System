-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2026 at 12:48 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.0.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_exam_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `student_exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_choice_id` int(11) DEFAULT NULL,
  `essay_answer` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obtained_marks` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `student_exam_id`, `question_id`, `selected_choice_id`, `essay_answer`, `obtained_marks`) VALUES
(1, 1, 1, 2, NULL, 2),
(2, 1, 2, 5, NULL, 0),
(21, 19, 4, 14, NULL, 5),
(25, 21, 9, 32, NULL, 0),
(26, 21, 10, 33, NULL, 3),
(27, 22, 12, 39, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `choice_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `choices`
--

INSERT INTO `choices` (`choice_id`, `question_id`, `choice_text`, `is_correct`) VALUES
(1, 1, 'HTML', 0),
(2, 1, 'SQL', 1),
(3, 1, 'Python', 0),
(4, 1, 'C++', 0),
(5, 2, 'صح', 0),
(6, 2, 'خطأ', 0),
(11, 4, 'sql server', 0),
(12, 4, 'my sql', 0),
(13, 4, 'sql lite', 0),
(14, 4, 'all of them', 1),
(29, 9, 'iyuftd', 1),
(30, 9, 'oiu8t87r', 0),
(31, 9, 'iuy7tr7', 0),
(32, 9, 'iuutf767', 0),
(33, 10, 'صح', 1),
(34, 10, 'خطأ', 0),
(39, 12, 'صح', 1),
(40, 12, 'خطأ', 0),
(41, 13, 'صح', 1),
(42, 13, 'خطأ', 0),
(43, 14, 'sql server', 1),
(44, 14, 'sql server', 0),
(45, 14, 'علبغعلعغ', 0),
(46, 14, 'ىلااع', 0),
(53, 1, 'صح', 1),
(54, 1, 'خطأ', 0),
(55, 2, 'HTML', 0),
(56, 2, 'SQL', 1),
(57, 2, 'Python', 0),
(58, 2, 'C++', 0);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_hours` int(11) DEFAULT 3,
  `instructor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `course_code`, `credit_hours`, `instructor_id`) VALUES
(1, 'نظم قواعد البيانات', 'CS401', 3, 1),
(2, 'لغة البرمجة c++', 'CS402', 3, 1),
(3, 'انظمو موازية وموزعة', 'CS461', 3, 2),
(4, 'تحليل وتصميم كينوني ', 'cs400', 3, 5),
(5, 'مترجمات ', 'cs403', 3, 9),
(6, 'برمجه حاسوب ', 'cs405', 3, 7),
(7, 'نظم تشغيل ', 'cs408', 3, 8),
(8, 'هندسه برمجيات ', 'cs409', 3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `academic_year`, `semester`) VALUES
(1, 1, 1, '2025-2026', 'الفصل الثاني');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_date` datetime NOT NULL,
  `duration` int(11) NOT NULL,
  `total_marks` int(11) DEFAULT 0,
  `exam_token` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `course_id`, `title`, `exam_date`, `duration`, `total_marks`, `exam_token`) VALUES
(1, 1, 'الامتحان النهائي - نظري', '2026-06-11 23:35:09', 60, 0, 'USZ26'),
(7, 1, 'الامتحان النصفي - نظري', '2026-06-15 23:35:09', 60, 5, '222z'),
(13, 1, 'ugdtd6f8u', '0000-00-00 00:00:00', 60, 8, '222a'),
(14, 1, 'نهائي c++', '0000-00-00 00:00:00', 60, 3, '444a'),
(15, 2, 'نهائي', '2026-07-09 23:06:00', 60, 21, '52f56'),
(16, 2, 'نظري', '2026-07-05 17:58:00', 60, 7, '693ef'),
(19, 2, 'نهاااااائي', '2026-07-16 02:39:00', 60, 1, '26061'),
(21, 2, 'نهائي نظري', '2026-07-18 22:12:00', 60, 1, ''),
(22, 2, 'نهائي نظري', '2026-07-18 22:12:00', 60, 0, ''),
(23, 2, 'نهائي نظري', '2026-07-18 22:12:00', 60, 0, ''),
(24, 3, 'تتتتتتت', '2026-07-25 23:03:00', 60, 0, ''),
(29, 2, 'تجريبي', '2026-07-25 01:39:00', 70, 1, 'Yy0oV'),
(30, 4, 'تجريبي', '2026-07-16 01:23:00', 60, 2, 'pj3ra'),
(31, 4, 'نظور', '2026-07-16 09:25:00', 60, 2, 'dvta0'),
(32, 4, 'من', '2026-07-18 09:54:00', 60, 2, 'h4dq4'),
(36, 7, 'نظم_نهائي', '2026-07-29 11:56:00', 60, 3, 'eISqA');

-- --------------------------------------------------------

--
-- Table structure for table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `exam_questions`
--

INSERT INTO `exam_questions` (`id`, `exam_id`, `question_id`) VALUES
(4, 29, 1),
(5, 21, 1),
(6, 30, 16),
(7, 30, 15),
(8, 31, 16),
(9, 31, 15),
(10, 32, 16),
(11, 32, 15),
(12, 36, 6),
(13, 36, 4),
(14, 36, 5);

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `academic_rank` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`instructor_id`, `user_id`, `academic_rank`, `department`) VALUES
(1, 2, 'عضو هيئة تدريس', 'تكنولوجيا المعلومات'),
(2, 4, '', ''),
(3, 7, 'رئيس قسم', 'علوم حاسوب'),
(4, 8, 'رئيس قسم', 'تكنولوجيا المعلومات'),
(5, 10, 'عضو هيئة تدريس', 'علوم حاسوب'),
(6, 11, 'عضو هيئة تدريس', 'نظم معلومات'),
(7, 12, 'عضو هيئة تدريس', 'علوم حاسوب'),
(8, 13, 'عضو هيئة تدريس', 'تكنولوجيا المعلومات'),
(9, 14, 'عضو هيئة تدريس', 'نظم معلومات');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `timestamp`) VALUES
(1, 3, 'الطالب دخل للامتحان [ الامتحان النهائي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-12 00:57:08'),
(2, 3, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-06-16 21:54:17'),
(3, 3, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-06-16 21:54:46'),
(4, 3, 'الطالب دخل للامتحان [ بايثون ] من جهاز ذو الأيبي: ::1', '2026-06-16 21:55:41'),
(5, 3, 'الطالب دخل للامتحان [ الامتحان النظري النصفي ] من جهاز ذو الأيبي: ::1', '2026-06-16 23:39:05'),
(6, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-16 23:47:50'),
(7, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-16 23:54:50'),
(8, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:24:33'),
(9, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:26:32'),
(10, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:30:31'),
(11, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:32:19'),
(12, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:36:07'),
(13, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:39:28'),
(14, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:44:58'),
(15, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 00:53:30'),
(16, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 01:01:27'),
(17, 3, 'الطالب دخل للامتحان [ الامتحان النصفي - نظري ] من جهاز ذو الأيبي: ::1', '2026-06-17 22:40:05'),
(18, 3, 'الطالب دخل للامتحان [ البغبغ ] من جهاز ذو الأيبي: ::1', '2026-06-21 13:53:01'),
(19, 3, 'الطالب دخل للامتحان [ ugdtd6f8u ] من جهاز ذو الأيبي: ::1', '2026-06-27 01:29:36'),
(20, 3, 'الطالب دخل للامتحان [ نهائي c++ ] من جهاز ذو الأيبي: ::1', '2026-06-30 13:05:57'),
(21, 3, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-08 23:57:32'),
(22, 3, 'الطالب دخل للامتحان [ عفهعقعثغفص ] من جهاز ذو الأيبي: ::1', '2026-07-08 23:59:52'),
(23, 3, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-13 01:48:12'),
(24, 3, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-13 01:49:04'),
(25, 3, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 01:50:30'),
(26, 5, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-13 02:22:27'),
(27, 5, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 02:22:51'),
(28, 3, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 02:35:08'),
(29, 15, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 03:16:14'),
(30, 16, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 03:25:41'),
(31, 15, 'الطالب دخل للامتحان [ نظور ] من جهاز ذو الأيبي: ::1', '2026-07-13 05:33:57'),
(32, 3, 'الطالب دخل للامتحان [ من ] من جهاز ذو الأيبي: ::1', '2026-07-13 06:55:35'),
(33, 3, 'الطالب دخل للامتحان [ نظور ] من جهاز ذو الأيبي: ::1', '2026-07-13 10:55:39'),
(34, 16, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-13 11:06:56'),
(35, 16, 'الطالب دخل للامتحان [ من ] من جهاز ذو الأيبي: ::1', '2026-07-13 11:07:22'),
(36, 15, 'محاولة فاشلة لدخول امتحان بمعطيات توكن خاطئة من الأيبي: ::1', '2026-07-13 12:11:32'),
(37, 15, 'الطالب دخل للامتحان [ نظم_نهائي ] من جهاز ذو الأيبي: ::1', '2026-07-13 12:12:23'),
(38, 15, 'الطالب دخل للامتحان [ تجريبي ] من جهاز ذو الأيبي: ::1', '2026-07-13 12:29:27'),
(39, 15, 'الطالب دخل للامتحان [ نهاااااائي ] من جهاز ذو الأيبي: ::1', '2026-07-13 12:33:16'),
(40, 16, 'الطالب دخل للامتحان [ نظم_نهائي ] من جهاز ذو الأيبي: ::1', '2026-07-13 12:59:12'),
(41, 3, 'الطالب دخل للامتحان [ نظم_نهائي ] من جهاز ذو الأيبي: ::1', '2026-07-13 13:14:37'),
(42, 5, 'الطالب دخل للامتحان [ نظم_نهائي ] من جهاز ذو الأيبي: ::1', '2026-07-13 13:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('mcq','truefalse','short','essay','programming') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`, `marks`) VALUES
(1, 1, 'ما هي اللغة الأساسية المستخدمة لإدارة قواعد البيانات 관계ية؟', 'mcq', 3),
(2, 1, 'يعتبر حقل المفتاح الأساسي (Primary Key) قابلاً لتكرار البيانات في الجدول الواحد.', 'truefalse', 2),
(4, 7, 'ماهي انواع قواعد البيانات', 'mcq', 5),
(9, 13, 'jihyuftdyfuguf', 'mcq', 5),
(10, 13, 'igyfttdtdy', 'truefalse', 3),
(12, 14, 'تعتبر لغة c++ حساسة لحالة الاحرف', 'truefalse', 3),
(13, 16, 'لابفغ', 'truefalse', 4),
(14, 16, 'فيق5يق', 'mcq', 3);

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

CREATE TABLE `question_bank` (
  `question_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('mcq','true_false') COLLATE utf8mb4_unicode_ci NOT NULL,
  `choice_1` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `choice_2` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `choice_3` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `choice_4` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_answer` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `marks` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank`
--

INSERT INTO `question_bank` (`question_id`, `course_id`, `exam_id`, `instructor_id`, `question_text`, `question_type`, `choice_1`, `choice_2`, `choice_3`, `choice_4`, `correct_answer`, `difficulty`, `marks`, `status`, `created_at`) VALUES
(1, 2, 19, 1, 'cout هي جملة الطباعة', '', NULL, NULL, NULL, NULL, NULL, 'medium', 1, 'approved', '2026-07-10 23:42:58'),
(2, 1, 1, 1, 'ما هي اللغة الأساسية المستخدمة لإدارة قواعد البيانات', 'mcq', NULL, NULL, NULL, NULL, NULL, 'medium', 5, 'pending', '2026-07-11 13:45:55'),
(3, 7, 36, 8, 'نظام التشغيل هو الوسيط بين المستخدم والحاسوب', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 3, 'approved', '2026-07-11 23:40:03'),
(4, 7, 36, 8, 'يمكن للحاسوب العمل بكفاءه دون نظام تشغيل', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'false', 'medium', 1, 'approved', '2026-07-11 23:48:19'),
(5, 7, 36, 8, 'اداره الذاكره من مهام نظام التشغيل', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 1, 'approved', '2026-07-11 23:49:33'),
(6, 7, 36, 8, 'ما الوظيفه الاساسيه لنظام التشغيل', 'mcq', 'تصميم البرامج', 'اداره موارد الحاسوب', 'ادره قواعد البيانات', 'تصفح الانترنت', '2', 'medium', 1, 'approved', '2026-07-11 23:52:03'),
(7, 6, NULL, 7, 'ما المقصود بالبرمجه', 'mcq', 'تصميم الصور', 'كتابه التعليمات للحاسوب لتنفيذ مهمه معينه', 'انشاء شبكات', 'اصلاح اجهزه', '2', 'medium', 1, 'pending', '2026-07-11 23:57:30'),
(8, 6, NULL, 7, 'اي جمله تستخدم لاتخاذ قرار في البرنامج', 'mcq', 'if', 'for', 'while', 'peint', '1', 'medium', 1, 'pending', '2026-07-11 23:59:33'),
(9, 6, NULL, 7, 'البرمجه هي عمليه كتابه تعليمات للحاسوب لتنفيذ مهمه معينه', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 1, 'pending', '2026-07-12 00:01:30'),
(10, 8, 30, 0, 'تحليل المتطلبات هي احدي مراحل تطوير البرمجيات', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 4, 'pending', '2026-07-12 00:07:10'),
(11, 8, 30, 0, 'مرحله الصيانه تاتي بعد تسليم النظام للمستخدم', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 1, 'pending', '2026-07-12 00:08:41'),
(12, 8, 30, 0, 'في اي مرحله يتم جمع احتياجات المستخدم', 'mcq', 'الاختبار', 'جمع المتطلبات', 'الصيانه', 'التنفيد', '2', 'medium', 1, 'pending', '2026-07-12 00:10:30'),
(13, 8, 30, 0, 'اي مرحله تاتي بعد مرحله التصميم', 'mcq', 'التنفيد', 'جمع المتطلبات', 'الصيانه', 'التخطيط', '1', 'medium', 3, 'pending', '2026-07-12 00:12:35'),
(14, 4, 32, 5, 'التحليل هو مرحله جمع وفهم متطلبات تامستخدم', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 1, 'pending', '2026-07-12 00:15:46'),
(15, 4, 32, 5, 'يمكن ان يحتوي الجدول علي اكثر من مفتاح اساسي', 'true_false', 'صح (True)', 'خطأ (False)', NULL, NULL, 'false', 'medium', 1, 'approved', '2026-07-12 00:16:43'),
(16, 4, 32, 5, 'ما الهدف من المفتاح الاساسي', 'mcq', 'حذف البيانات', 'تميز كل سجل بشكل فريد', 'انشاء تقارير', 'حفظ تقارير', '2', 'medium', 1, 'approved', '2026-07-12 00:19:01'),
(20, 4, 32, 10, 'مخطط حالة الاستخدام يعرض الوظائف الرئيسية للنظام', '', 'صح (True)', 'خطأ (False)', NULL, NULL, 'true', 'medium', 1, 'pending', '2026-07-13 01:18:18');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `result_id` int(11) NOT NULL,
  `student_exam_id` int(11) NOT NULL,
  `total_obtained_marks` int(11) NOT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`result_id`, `student_exam_id`, `total_obtained_marks`, `grade`, `published_date`) VALUES
(1, 1, 2, 'راسب', '2026-06-12 01:58:59'),
(19, 19, 5, 'ناجح', '2026-06-17 22:40:37'),
(21, 21, 3, 'راسب', '2026-06-27 01:32:12'),
(22, 22, 3, 'ناجح', '2026-06-30 13:07:36'),
(24, 24, 0, 'راسب', '2026-07-13 01:51:47'),
(25, 25, 0, 'راسب', '2026-07-13 02:26:00'),
(26, 26, 0, 'راسب', '2026-07-13 02:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `level`, `department`) VALUES
(1, 3, 4, 'علوم حاسوب'),
(2, 5, 3, 'تكنولوجيا المعلومات'),
(3, 15, 4, 'علوم حاسوب'),
(4, 16, 4, 'علوم حاسوب');

-- --------------------------------------------------------

--
-- Table structure for table `student_exams`
--

CREATE TABLE `student_exams` (
  `student_exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','completed','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_exams`
--

INSERT INTO `student_exams` (`student_exam_id`, `student_id`, `exam_id`, `start_time`, `end_time`, `ip_address`, `status`) VALUES
(1, 1, 1, '2026-06-12 00:57:08', '2026-06-16 21:57:55', '::1', 'completed'),
(19, 1, 7, '2026-06-17 22:40:05', '2026-06-17 22:40:37', '::1', 'completed'),
(21, 1, 13, '2026-06-27 01:29:36', '2026-06-27 01:32:12', '::1', 'completed'),
(22, 1, 14, '2026-06-30 13:05:57', '2026-06-30 13:07:36', '::1', 'completed'),
(24, 1, 29, '2026-07-13 01:50:30', '2026-07-13 01:51:47', '::1', 'completed'),
(25, 2, 30, '2026-07-13 02:22:51', '2026-07-13 02:26:00', '::1', 'completed'),
(26, 1, 30, '2026-07-13 02:35:08', '2026-07-13 02:36:12', '::1', 'completed'),
(27, 3, 30, '2026-07-13 03:16:14', NULL, '::1', 'active'),
(28, 4, 30, '2026-07-13 03:25:41', NULL, '::1', 'active'),
(29, 3, 31, '2026-07-13 05:33:57', NULL, '::1', 'completed'),
(35, 1, 32, '2026-07-13 06:55:35', NULL, '::1', 'active'),
(36, 3, 32, '2026-07-13 06:55:35', NULL, NULL, 'completed'),
(37, 1, 31, '2026-07-13 10:55:39', NULL, '::1', 'active'),
(38, 4, 32, '2026-07-13 11:07:22', NULL, '::1', 'active'),
(42, 3, 36, '2026-07-13 12:12:23', NULL, '::1', 'completed'),
(44, 3, 29, '2026-07-13 12:29:27', NULL, '::1', 'active'),
(45, 3, 19, '2026-07-13 12:33:16', NULL, '::1', 'active'),
(47, 4, 36, '2026-07-13 12:59:12', NULL, '::1', 'active'),
(51, 1, 36, '2026-07-13 13:14:37', NULL, '::1', 'active'),
(52, 2, 36, '2026-07-13 13:18:46', NULL, '::1', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `student_responses`
--

CREATE TABLE `student_responses` (
  `response_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `chosen_answer` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_earned` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_responses`
--

INSERT INTO `student_responses` (`response_id`, `student_id`, `exam_id`, `question_id`, `chosen_answer`, `is_correct`, `marks_earned`, `created_at`) VALUES
(1, 15, 31, 15, '0', NULL, NULL, '2026-07-13 03:20:59'),
(2, 15, 31, 16, '2', NULL, NULL, '2026-07-13 03:21:05'),
(3, 15, 31, 15, '0', NULL, NULL, '2026-07-13 03:24:29'),
(4, 15, 31, 16, '2', NULL, NULL, '2026-07-13 03:24:31'),
(5, 3, 32, 15, '0', NULL, NULL, '2026-07-13 03:55:43'),
(6, 3, 32, 16, '2', NULL, NULL, '2026-07-13 03:55:45'),
(7, 16, 32, 15, '0', NULL, NULL, '2026-07-13 08:07:35'),
(8, 16, 32, 16, '2', NULL, NULL, '2026-07-13 08:07:45'),
(9, 16, 36, 3, 'true', NULL, NULL, '2026-07-13 08:58:14'),
(10, 16, 36, 4, 'false', NULL, NULL, '2026-07-13 08:58:19'),
(11, 16, 36, 5, 'true', NULL, NULL, '2026-07-13 08:58:25'),
(12, 16, 36, 6, '2', NULL, NULL, '2026-07-13 08:58:30'),
(13, 15, 36, 3, '0', NULL, NULL, '2026-07-13 09:27:15'),
(14, 15, 19, 1, '0', NULL, NULL, '2026-07-13 09:35:32'),
(15, 5, 36, 3, 'true', NULL, NULL, '2026-07-13 10:21:05'),
(16, 5, 36, 4, 'false', NULL, NULL, '2026-07-13 10:21:07'),
(17, 5, 36, 5, 'true', NULL, NULL, '2026-07-13 10:21:10'),
(18, 5, 36, 6, '2', NULL, NULL, '2026-07-13 10:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `university_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','dean','head_of_dept','instructor','control','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `university_id`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'مدير النظام الافتراضي', 'admin_2026', 'admin@usz.edu.ye', '123', 'admin', '2026-06-11 20:35:09'),
(2, 'الدكتور محمد عبد الله', 'inst_2026', 'dr.mohamed@usz.edu.ye', '123', 'instructor', '2026-06-11 20:35:09'),
(3, 'الطالب علي أحمد ناصر', '220101050', 'ali.stud@usz.edu.ye', '123', 'student', '2026-06-11 20:35:09'),
(4, 'د. معاذ الصبري', '45678', 'dr_moadh@usr.edu', '123', 'instructor', '2026-07-05 10:01:59'),
(5, 'رؤى محمد خالد', '78905', 'roaeheart15597@gmail.com', '123', 'student', '2026-07-05 14:38:18'),
(6, 'كنترول', '3456', 'control@gmail.com', '123', 'control', '2026-07-10 20:28:41'),
(7, 'د اسامة سيف', '7890', 'osama@gmail.com', '123', 'head_of_dept', '2026-07-10 22:07:30'),
(8, 'د خالد البراحي', '12345', 'Khalid@gmail.com', '123', 'head_of_dept', '2026-07-11 14:28:11'),
(9, 'د مقبول الكامل', '6789', 'makboul@gmail.com', '123', 'dean', '2026-07-11 20:32:35'),
(10, 'عبد العزيز ثوابه', '2222', 'aa@gmail.com', '123', 'instructor', '2026-07-11 22:50:40'),
(11, 'يحي البركاني', '3333', 'ee@gmail.com', '123', 'instructor', '2026-07-11 22:53:31'),
(12, 'مختار السروري', '4444', 'mm@gmail.com', '123', 'instructor', '2026-07-11 22:55:26'),
(13, 'فارس الهدشاء', '5555', 'ff@gmail.com', '123', 'instructor', '2026-07-11 22:57:01'),
(14, 'حمود الشلبي ', '6666', 'hh@gmail.com', '123', 'instructor', '2026-07-11 22:59:06'),
(15, 'نزيهة الراشدي', '0000', 'naziha@gmail.com', '123', 'student', '2026-07-12 23:50:28'),
(16, 'روان خالد', '9090', 'rawan@gmail.com', '123', 'student', '2026-07-13 00:25:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `unique_answer` (`student_exam_id`,`question_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `selected_choice_id` (`selected_choice_id`);

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`choice_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `idx_course_code` (`course_code`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`,`academic_year`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `question_bank`
--
ALTER TABLE `question_bank`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD UNIQUE KEY `student_exam_id` (`student_exam_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `student_exams`
--
ALTER TABLE `student_exams`
  ADD PRIMARY KEY (`student_exam_id`),
  ADD UNIQUE KEY `unique_student_exam` (`student_id`,`exam_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `student_responses`
--
ALTER TABLE `student_responses`
  ADD PRIMARY KEY (`response_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `university_id` (`university_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_university_id` (`university_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `choice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `question_bank`
--
ALTER TABLE `question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_exams`
--
ALTER TABLE `student_exams`
  MODIFY `student_exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `student_responses`
--
ALTER TABLE `student_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`student_exam_id`) REFERENCES `student_exams` (`student_exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`selected_choice_id`) REFERENCES `choices` (`choice_id`) ON DELETE SET NULL;

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `question_bank` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `instructors`
--
ALTER TABLE `instructors`
  ADD CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_bank`
--
ALTER TABLE `question_bank`
  ADD CONSTRAINT `question_bank_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_exam_id`) REFERENCES `student_exams` (`student_exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_exams`
--
ALTER TABLE `student_exams`
  ADD CONSTRAINT `student_exams_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_exams_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
