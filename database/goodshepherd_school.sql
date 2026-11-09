-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 07, 2026 at 10:29 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goodshepherd_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(10) UNSIGNED NOT NULL,
  `year_name` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year_name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`) VALUES
(1, '2026', '2026-02-02', '2026-12-04', 1, 'Active', '2026-07-25 20:45:14', '2026-07-25 20:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_registers`
--

CREATE TABLE `attendance_registers` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `stream_id` int(10) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `session` enum('Morning','Afternoon') NOT NULL DEFAULT 'Morning',
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_registers`
--

INSERT INTO `attendance_registers` (`id`, `academic_year_id`, `term_id`, `class_id`, `stream_id`, `attendance_date`, `session`, `recorded_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 4, NULL, '2026-07-26', 'Morning', 1, NULL, '2026-07-26 11:47:11', '2026-07-26 15:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_level` int(11) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_code`, `class_level`, `status`, `created_at`) VALUES
(1, 'Baby Class', 'BC', 1, 'Active', '2026-07-23 22:46:41'),
(2, 'Middle Class', 'MC', 2, 'Active', '2026-07-23 22:46:41'),
(3, 'Top Class', 'TC', 3, 'Active', '2026-07-23 22:46:41'),
(4, 'Primary One', 'P1', 4, 'Active', '2026-07-23 22:46:41'),
(5, 'Primary Two', 'P2', 5, 'Active', '2026-07-23 22:46:41'),
(6, 'Primary Three', 'P3', 6, 'Active', '2026-07-23 22:46:41'),
(7, 'Primary Four', 'P4', 7, 'Active', '2026-07-23 22:46:41'),
(8, 'Primary Five', 'P5', 8, 'Active', '2026-07-23 22:46:41'),
(9, 'Primary Six', 'P6', 9, 'Active', '2026-07-23 22:46:41'),
(10, 'Primary Seven', 'P7', 10, 'Active', '2026-07-23 22:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `examinations`
--

CREATE TABLE `examinations` (
  `id` int(10) UNSIGNED NOT NULL,
  `examination_type_id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `examination_name` varchar(150) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Draft','Open','Closed','Published') NOT NULL DEFAULT 'Draft',
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examinations`
--

INSERT INTO `examinations` (`id`, `examination_type_id`, `academic_year_id`, `term_id`, `examination_name`, `start_date`, `end_date`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 2, 'EOT', '2026-05-25', '2026-08-21', 'Open', NULL, 1, '2026-07-26 16:36:29', '2026-07-26 16:37:05'),
(2, 3, 1, 2, 'MID', '2026-05-25', '2026-08-21', 'Open', NULL, 1, '2026-07-26 21:30:45', '2026-07-26 21:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `examination_classes`
--

CREATE TABLE `examination_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `examination_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `stream_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examination_classes`
--

INSERT INTO `examination_classes` (`id`, `examination_id`, `class_id`, `stream_id`, `status`, `created_at`) VALUES
(1, 1, 4, NULL, 'Active', '2026-07-26 21:27:30'),
(2, 2, 4, NULL, 'Active', '2026-07-26 21:31:13');

-- --------------------------------------------------------

--
-- Table structure for table `examination_subjects`
--

CREATE TABLE `examination_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `examination_class_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `maximum_mark` decimal(6,2) NOT NULL DEFAULT 100.00,
  `pass_mark` decimal(6,2) NOT NULL DEFAULT 50.00,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 100.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examination_subjects`
--

INSERT INTO `examination_subjects` (`id`, `examination_class_id`, `subject_id`, `maximum_mark`, `pass_mark`, `weight_percentage`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 11, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(2, 1, 9, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(3, 1, 1, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(4, 1, 3, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(5, 1, 12, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(6, 1, 6, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(7, 1, 7, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(8, 1, 8, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(9, 1, 2, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(10, 1, 10, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(11, 1, 5, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(12, 1, 4, 100.00, 50.00, 100.00, 'Active', '2026-07-26 21:27:30', '2026-07-26 21:27:30'),
(13, 2, 11, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(14, 2, 9, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(15, 2, 1, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(16, 2, 3, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(17, 2, 12, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(18, 2, 6, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(19, 2, 7, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(20, 2, 8, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(21, 2, 2, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(22, 2, 10, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(23, 2, 5, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13'),
(24, 2, 4, 100.00, 40.00, 100.00, 'Active', '2026-07-26 21:31:13', '2026-07-26 21:31:13');

-- --------------------------------------------------------

--
-- Table structure for table `examination_types`
--

CREATE TABLE `examination_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `type_code` varchar(30) NOT NULL,
  `default_weight` decimal(5,2) NOT NULL DEFAULT 100.00,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examination_types`
--

INSERT INTO `examination_types` (`id`, `type_name`, `type_code`, `default_weight`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Beginning of Term Examination', 'BOT', 20.00, 'Assessment conducted at the beginning of the term', 'Active', '2026-07-26 16:34:58', '2026-07-26 16:34:58'),
(2, 'Continuous Assessment', 'CA', 20.00, 'Ongoing classroom assessment', 'Active', '2026-07-26 16:34:58', '2026-07-26 16:34:58'),
(3, 'Mid-Term Examination', 'MID', 30.00, 'Assessment conducted in the middle of the term', 'Active', '2026-07-26 16:34:58', '2026-07-26 16:34:58'),
(4, 'End of Term Examination', 'EOT', 50.00, 'Final assessment conducted at the end of the term', 'Active', '2026-07-26 16:34:58', '2026-07-26 16:34:58'),
(5, 'Mock Examination', 'MOCK', 100.00, 'Preparatory examination, normally for Primary Seven', 'Active', '2026-07-26 16:34:58', '2026-07-26 16:34:58');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `expense_category` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank','Mobile Money','Other') NOT NULL,
  `expense_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_categories`
--

CREATE TABLE `fee_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(120) NOT NULL,
  `category_code` varchar(30) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `category_type` enum('Mandatory','Optional') NOT NULL DEFAULT 'Mandatory',
  `charge_frequency` enum('Once','Termly','Annually','Monthly') NOT NULL DEFAULT 'Termly',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank','Mobile Money','Other') NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `fee_category_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_scales`
--

CREATE TABLE `grading_scales` (
  `id` int(10) UNSIGNED NOT NULL,
  `grade_name` varchar(10) NOT NULL,
  `minimum_mark` decimal(6,2) NOT NULL,
  `maximum_mark` decimal(6,2) NOT NULL,
  `points` decimal(5,2) DEFAULT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `applicable_level` enum('Nursery','Primary','All') NOT NULL DEFAULT 'Primary',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_scales`
--

INSERT INTO `grading_scales` (`id`, `grade_name`, `minimum_mark`, `maximum_mark`, `points`, `remark`, `applicable_level`, `status`, `created_at`) VALUES
(1, 'D1', 90.00, 100.00, 1.00, 'Excellent', 'Primary', 'Active', '2026-07-26 16:34:58'),
(2, 'D2', 80.00, 89.99, 2.00, 'Very Good', 'Primary', 'Active', '2026-07-26 16:34:58'),
(3, 'C3', 70.00, 79.99, 3.00, 'Good', 'Primary', 'Active', '2026-07-26 16:34:58'),
(4, 'C4', 60.00, 69.99, 4.00, 'Credit', 'Primary', 'Active', '2026-07-26 16:34:58'),
(5, 'C5', 55.00, 59.99, 5.00, 'Credit', 'Primary', 'Active', '2026-07-26 16:34:58'),
(6, 'C6', 50.00, 54.99, 6.00, 'Credit', 'Primary', 'Active', '2026-07-26 16:34:58'),
(7, 'P7', 45.00, 49.99, 7.00, 'Pass', 'Primary', 'Active', '2026-07-26 16:34:58'),
(8, 'P8', 40.00, 44.99, 8.00, 'Pass', 'Primary', 'Active', '2026-07-26 16:34:58'),
(9, 'F9', 0.00, 39.99, 9.00, 'Fail', 'Primary', 'Active', '2026-07-26 16:34:58');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(10) UNSIGNED NOT NULL,
  `method_name` varchar(80) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`, `status`, `created_at`) VALUES
(1, 'Cash', 'Active', '2026-08-03 22:34:59'),
(2, 'Bank', 'Active', '2026-08-03 22:34:59'),
(3, 'Mobile Money', 'Active', '2026-08-03 22:34:59'),
(4, 'Cheque', 'Active', '2026-08-03 22:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `pupils`
--

CREATE TABLE `pupils` (
  `id` int(10) UNSIGNED NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `emis_number` varchar(60) DEFAULT NULL,
  `lin_number` varchar(60) DEFAULT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(80) NOT NULL DEFAULT 'Ugandan',
  `religion` varchar(80) DEFAULT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `stream_id` int(10) UNSIGNED DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `admission_type` enum('New Admission','Transfer','Re-admission') NOT NULL DEFAULT 'New Admission',
  `former_school` varchar(180) DEFAULT NULL,
  `class_name` varchar(30) DEFAULT NULL,
  `stream_name` varchar(30) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `parish` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_phone` varchar(30) DEFAULT NULL,
  `father_occupation` varchar(120) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_phone` varchar(30) DEFAULT NULL,
  `mother_occupation` varchar(120) DEFAULT NULL,
  `guardian_name` varchar(150) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `guardian_relationship` varchar(80) DEFAULT NULL,
  `orphan_status` enum('Not Orphan','Single Orphan - Father Deceased','Single Orphan - Mother Deceased','Double Orphan') NOT NULL DEFAULT 'Not Orphan',
  `blood_group` varchar(10) DEFAULT NULL,
  `medical_condition` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `has_disability` tinyint(1) NOT NULL DEFAULT 0,
  `disability_type` varchar(150) DEFAULT NULL,
  `special_needs` text DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `emergency_contact_relationship` varchar(80) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `pupil_status` enum('Active','Transferred','Completed','Dropped') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pupils`
--

INSERT INTO `pupils` (`id`, `admission_number`, `emis_number`, `lin_number`, `first_name`, `middle_name`, `last_name`, `gender`, `date_of_birth`, `nationality`, `religion`, `class_id`, `stream_id`, `admission_date`, `admission_type`, `former_school`, `class_name`, `stream_name`, `district`, `county`, `sub_county`, `parish`, `village`, `home_address`, `father_name`, `father_phone`, `father_occupation`, `mother_name`, `mother_phone`, `mother_occupation`, `guardian_name`, `guardian_phone`, `guardian_relationship`, `orphan_status`, `blood_group`, `medical_condition`, `allergies`, `has_disability`, `disability_type`, `special_needs`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `photo`, `pupil_status`, `created_at`, `updated_at`) VALUES
(1, 'GS-PS-2002', NULL, NULL, 'MASAI', NULL, 'TIM', 'Male', NULL, 'Ugandan', NULL, 4, NULL, '2026-07-25', 'Transfer', NULL, 'Primary One', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not Orphan', 'A-', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'uploads/pupils/77fb0cda897c537ed07c7247a2e09df6.jpg', 'Active', '2026-07-25 19:12:57', '2026-07-30 14:10:06'),
(2, '26/GS/PRIMARYTWO/001', NULL, NULL, 'Alexa', NULL, 'Noel', 'Female', NULL, 'Ugandan', NULL, 5, NULL, '2026-07-27', 'Transfer', NULL, 'Primary Two', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not Orphan', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-07-27 18:22:40', '2026-07-27 18:25:52'),
(3, '26/GS/P4/001', NULL, NULL, 'Jkisa', NULL, 'allan', 'Male', NULL, 'Ugandan', NULL, 7, NULL, '2026-07-27', 'New Admission', NULL, 'Primary Four', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not Orphan', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-07-27 18:26:51', '2026-07-27 18:26:51'),
(4, '26/GS/P4/002', NULL, NULL, 'Hellen', NULL, 'Kata', 'Female', NULL, 'Ugandan', NULL, 7, NULL, '2026-07-27', 'Transfer', NULL, 'Primary Four', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not Orphan', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-07-27 18:28:26', '2026-07-27 18:28:26');

-- --------------------------------------------------------

--
-- Table structure for table `pupil_attendance`
--

CREATE TABLE `pupil_attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `attendance_register_id` int(10) UNSIGNED NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Present',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pupil_attendance`
--

INSERT INTO `pupil_attendance` (`id`, `attendance_register_id`, `pupil_id`, `attendance_status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Present', 'Always present', '2026-07-26 11:47:11', '2026-07-26 15:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `pupil_invoices`
--

CREATE TABLE `pupil_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `invoice_status` enum('Draft','Unpaid','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Unpaid',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pupil_marks`
--

CREATE TABLE `pupil_marks` (
  `id` int(10) UNSIGNED NOT NULL,
  `examination_subject_id` int(10) UNSIGNED NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `mark_obtained` decimal(6,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `points` decimal(5,2) DEFAULT NULL,
  `teacher_remark` varchar(255) DEFAULT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `entered_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pupil_marks`
--

INSERT INTO `pupil_marks` (`id`, `examination_subject_id`, `pupil_id`, `mark_obtained`, `grade`, `points`, `teacher_remark`, `is_absent`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 60.00, 'C4', 4.00, 'Credit', 0, 1, '2026-07-26 21:28:14', '2026-07-26 21:28:14'),
(2, 2, 1, 45.00, 'P7', 7.00, 'Pass', 0, 1, '2026-07-26 21:28:43', '2026-07-26 21:28:43'),
(3, 13, 1, 78.00, 'C3', 3.00, 'Good', 0, 1, '2026-07-26 21:31:58', '2026-07-26 21:31:58'),
(4, 18, 1, 90.00, 'D1', 1.00, 'Excellent', 0, 1, '2026-07-26 21:32:13', '2026-07-26 21:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `pupil_payments`
--

CREATE TABLE `pupil_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_number` varchar(60) NOT NULL,
  `receipt_number` varchar(60) NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `payment_method_id` int(10) UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `amount_received` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payer_name` varchar(150) DEFAULT NULL,
  `transaction_reference` varchar(120) DEFAULT NULL,
  `payment_status` enum('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Completed',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card_comments`
--

CREATE TABLE `report_card_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `examination_class_id` int(10) UNSIGNED NOT NULL,
  `pupil_id` int(10) UNSIGNED NOT NULL,
  `days_open` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `days_present` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `class_teacher_comment` text DEFAULT NULL,
  `head_teacher_comment` text DEFAULT NULL,
  `promotion_status` enum('Promoted','Repeat','Pending') NOT NULL DEFAULT 'Pending',
  `next_class_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_number` varchar(50) NOT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(80) NOT NULL DEFAULT 'Ugandan',
  `religion` varchar(80) DEFAULT NULL,
  `national_id_number` varchar(40) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed','Separated') DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `alternative_phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `staff_category` enum('Teaching Staff','Non-Teaching Staff','Administration') NOT NULL DEFAULT 'Teaching Staff',
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `employment_type` enum('Permanent','Contract','Part-Time','Volunteer','Probation') NOT NULL DEFAULT 'Permanent',
  `appointment_date` date DEFAULT NULL,
  `qualification` varchar(180) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `teaching_registration_number` varchar(80) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
  `nssf_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `bank_account_number` varchar(80) DEFAULT NULL,
  `next_of_kin_name` varchar(150) DEFAULT NULL,
  `next_of_kin_phone` varchar(30) DEFAULT NULL,
  `next_of_kin_relationship` varchar(80) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `medical_information` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_status` enum('Active','On Leave','Suspended','Retired','Resigned','Terminated','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_number`, `first_name`, `middle_name`, `last_name`, `full_name`, `gender`, `date_of_birth`, `nationality`, `religion`, `national_id_number`, `marital_status`, `phone`, `alternative_phone`, `email`, `district`, `sub_county`, `village`, `home_address`, `staff_category`, `department`, `designation`, `employment_type`, `appointment_date`, `qualification`, `specialization`, `teaching_registration_number`, `tin_number`, `nssf_number`, `bank_name`, `bank_account_name`, `bank_account_number`, `next_of_kin_name`, `next_of_kin_phone`, `next_of_kin_relationship`, `emergency_contact_name`, `emergency_contact_phone`, `medical_information`, `photo`, `position`, `employment_status`, `created_at`, `updated_at`) VALUES
(1, 'GS-T001', 'Masai', NULL, 'Timothy', 'Masai  Timothy', 'Male', '2026-07-08', 'Ugandan', 'Seventh-day Adventist', NULL, 'Married', '+2567787656544', NULL, NULL, NULL, NULL, NULL, NULL, 'Teaching Staff', NULL, 'class teacher', 'Contract', '2026-07-17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'class teacher', 'Active', '2026-07-25 21:30:05', '2026-07-26 11:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `streams`
--

CREATE TABLE `streams` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `stream_name` varchar(50) NOT NULL,
  `stream_code` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `streams`
--

INSERT INTO `streams` (`id`, `class_id`, `stream_name`, `stream_code`, `status`, `created_at`) VALUES
(1, 1, 'Stream A', 'BC-A', 'Active', '2026-07-23 22:46:41'),
(2, 2, 'Stream A', 'MC-A', 'Active', '2026-07-23 22:46:41'),
(3, 4, 'Stream A', 'P1-A', 'Active', '2026-07-23 22:46:41'),
(4, 5, 'Stream A', 'P2-A', 'Active', '2026-07-23 22:46:41'),
(5, 6, 'Stream A', 'P3-A', 'Active', '2026-07-23 22:46:41'),
(6, 7, 'Stream A', 'P4-A', 'Active', '2026-07-23 22:46:41'),
(7, 8, 'Stream A', 'P5-A', 'Active', '2026-07-23 22:46:41'),
(8, 9, 'Stream A', 'P6-A', 'Active', '2026-07-23 22:46:41'),
(9, 10, 'Stream A', 'P7-A', 'Active', '2026-07-23 22:46:41'),
(10, 3, 'Stream A', 'TC-A', 'Active', '2026-07-23 22:46:41'),
(16, 1, 'Stream B', 'BC-B', 'Active', '2026-07-23 22:46:41'),
(17, 2, 'Stream B', 'MC-B', 'Active', '2026-07-23 22:46:41'),
(18, 4, 'Stream B', 'P1-B', 'Active', '2026-07-23 22:46:41'),
(19, 5, 'Stream B', 'P2-B', 'Active', '2026-07-23 22:46:41'),
(20, 6, 'Stream B', 'P3-B', 'Active', '2026-07-23 22:46:41'),
(21, 7, 'Stream B', 'P4-B', 'Active', '2026-07-23 22:46:41'),
(22, 8, 'Stream B', 'P5-B', 'Active', '2026-07-23 22:46:41'),
(23, 9, 'Stream B', 'P6-B', 'Active', '2026-07-23 22:46:41'),
(24, 10, 'Stream B', 'P7-B', 'Active', '2026-07-23 22:46:41'),
(25, 3, 'Stream B', 'TC-B', 'Active', '2026-07-23 22:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(30) NOT NULL,
  `subject_category` enum('Core','Optional','Co-curricular') NOT NULL DEFAULT 'Core',
  `applicable_level` enum('Nursery','Lower Primary','Upper Primary','All') NOT NULL DEFAULT 'All',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `subject_category`, `applicable_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'English Language', 'ENG', 'Core', 'All', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(2, 'Mathematics', 'MTC', 'Core', 'All', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(3, 'Integrated Science', 'SCI', 'Core', 'Upper Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(4, 'Social Studies', 'SST', 'Core', 'Upper Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(5, 'Religious Education', 'RE', 'Core', 'All', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(6, 'Literacy I', 'LIT1', 'Core', 'Lower Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(7, 'Literacy II', 'LIT2', 'Core', 'Lower Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(8, 'Local Language', 'LLG', 'Core', 'Lower Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(9, 'Creative Performing Arts', 'CPA', 'Co-curricular', 'All', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(10, 'Physical Education', 'PE', 'Co-curricular', 'All', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(11, 'Computer Studies', 'ICT', 'Optional', 'Upper Primary', 'Active', '2026-07-25 20:45:15', '2026-07-25 20:45:15'),
(12, 'Islam', 'IS', 'Optional', 'Upper Primary', 'Active', '2026-07-25 20:47:31', '2026-07-25 20:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_allocations`
--

CREATE TABLE `teacher_allocations` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `stream_id` int(10) UNSIGNED DEFAULT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `is_class_teacher` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_allocations`
--

INSERT INTO `teacher_allocations` (`id`, `staff_id`, `academic_year_id`, `term_id`, `class_id`, `stream_id`, `subject_id`, `is_class_teacher`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 7, 6, 11, 1, 'Active', '2026-07-26 11:31:32', '2026-07-26 11:31:32'),
(2, 1, 1, 3, 2, NULL, 12, 0, 'Active', '2026-07-26 11:32:14', '2026-07-26 11:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `term_name` enum('Term One','Term Two','Term Three') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `academic_year_id`, `term_name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Term One', '2026-02-02', '2026-05-01', 0, 'Closed', '2026-07-25 20:45:14', '2026-07-25 20:45:14'),
(2, 1, 'Term Two', '2026-05-25', '2026-08-21', 1, 'Active', '2026-07-25 20:45:14', '2026-07-25 20:45:14'),
(3, 1, 'Term Three', '2026-09-14', '2026-12-04', 0, 'Active', '2026-07-25 20:45:14', '2026-07-25 20:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('administrator','headteacher','bursar','teacher') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@goodshepherd.local', '$2y$10$GKdFSjEKWGKGMZ96BlqmUuHoGhO510RHy1Ic5traVMbU4s7KVIwS.', 'administrator', 'active', '2026-11-08 00:26:46', '2026-07-22 20:38:23', '2026-11-07 21:26:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_name` (`year_name`);

--
-- Indexes for table `attendance_registers`
--
ALTER TABLE `attendance_registers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance_register` (`academic_year_id`,`term_id`,`class_id`,`stream_id`,`attendance_date`,`session`),
  ADD KEY `fk_attendance_register_term` (`term_id`),
  ADD KEY `fk_attendance_register_class` (`class_id`),
  ADD KEY `fk_attendance_register_stream` (`stream_id`),
  ADD KEY `fk_attendance_register_user` (`recorded_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_name` (`class_name`),
  ADD UNIQUE KEY `class_code` (`class_code`);

--
-- Indexes for table `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_examination_name` (`academic_year_id`,`term_id`,`examination_name`),
  ADD KEY `fk_examination_type` (`examination_type_id`),
  ADD KEY `fk_examination_term` (`term_id`),
  ADD KEY `fk_examination_creator` (`created_by`);

--
-- Indexes for table `examination_classes`
--
ALTER TABLE `examination_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_class_examination` (`examination_id`),
  ADD KEY `fk_exam_class_class` (`class_id`),
  ADD KEY `fk_exam_class_stream` (`stream_id`);

--
-- Indexes for table `examination_subjects`
--
ALTER TABLE `examination_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_class_subject` (`examination_class_id`,`subject_id`),
  ADD KEY `fk_exam_subject_subject` (`subject_id`);

--
-- Indexes for table `examination_types`
--
ALTER TABLE `examination_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`),
  ADD UNIQUE KEY `type_code` (`type_code`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expense_user` (`recorded_by`);

--
-- Indexes for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fee_category_name` (`category_name`),
  ADD UNIQUE KEY `unique_fee_category_code` (`category_code`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `fk_payment_pupil` (`pupil_id`),
  ADD KEY `fk_payment_user` (`recorded_by`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fee_structure` (`academic_year_id`,`term_id`,`class_id`,`fee_category_id`),
  ADD KEY `fk_fee_structure_term` (`term_id`),
  ADD KEY `fk_fee_structure_class` (`class_id`),
  ADD KEY `fk_fee_structure_category` (`fee_category_id`);

--
-- Indexes for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade_range` (`grade_name`,`applicable_level`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment_method_name` (`method_name`);

--
-- Indexes for table `pupils`
--
ALTER TABLE `pupils`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`);

--
-- Indexes for table `pupil_attendance`
--
ALTER TABLE `pupil_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pupil_attendance` (`attendance_register_id`,`pupil_id`),
  ADD KEY `fk_pupil_attendance_pupil` (`pupil_id`);

--
-- Indexes for table `pupil_invoices`
--
ALTER TABLE `pupil_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  ADD KEY `idx_invoice_period` (`academic_year_id`,`term_id`),
  ADD KEY `idx_invoice_pupil` (`pupil_id`),
  ADD KEY `fk_invoice_class` (`class_id`),
  ADD KEY `fk_invoice_term` (`term_id`);

--
-- Indexes for table `pupil_marks`
--
ALTER TABLE `pupil_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pupil_subject_mark` (`examination_subject_id`,`pupil_id`),
  ADD KEY `fk_pupil_mark_pupil` (`pupil_id`),
  ADD KEY `fk_pupil_mark_user` (`entered_by`);

--
-- Indexes for table `pupil_payments`
--
ALTER TABLE `pupil_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment_number` (`payment_number`),
  ADD UNIQUE KEY `unique_receipt_number` (`receipt_number`),
  ADD KEY `idx_payment_period` (`academic_year_id`,`term_id`),
  ADD KEY `idx_payment_pupil` (`pupil_id`),
  ADD KEY `fk_pupil_payment_term` (`term_id`),
  ADD KEY `fk_pupil_payment_method` (`payment_method_id`);

--
-- Indexes for table `report_card_comments`
--
ALTER TABLE `report_card_comments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pupil_report` (`examination_class_id`,`pupil_id`),
  ADD KEY `fk_report_pupil` (`pupil_id`),
  ADD KEY `fk_report_next_class` (`next_class_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_number` (`staff_number`);

--
-- Indexes for table `streams`
--
ALTER TABLE `streams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_stream` (`class_id`,`stream_name`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `teacher_allocations`
--
ALTER TABLE `teacher_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_assignment` (`staff_id`,`academic_year_id`,`term_id`,`class_id`,`stream_id`,`subject_id`),
  ADD KEY `fk_teacher_allocation_year` (`academic_year_id`),
  ADD KEY `fk_teacher_allocation_term` (`term_id`),
  ADD KEY `fk_teacher_allocation_class` (`class_id`),
  ADD KEY `fk_teacher_allocation_stream` (`stream_id`),
  ADD KEY `fk_teacher_allocation_subject` (`subject_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_year_term` (`academic_year_id`,`term_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance_registers`
--
ALTER TABLE `attendance_registers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `examination_classes`
--
ALTER TABLE `examination_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `examination_subjects`
--
ALTER TABLE `examination_subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `examination_types`
--
ALTER TABLE `examination_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_categories`
--
ALTER TABLE `fee_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_scales`
--
ALTER TABLE `grading_scales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pupils`
--
ALTER TABLE `pupils`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pupil_attendance`
--
ALTER TABLE `pupil_attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pupil_invoices`
--
ALTER TABLE `pupil_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pupil_marks`
--
ALTER TABLE `pupil_marks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pupil_payments`
--
ALTER TABLE `pupil_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card_comments`
--
ALTER TABLE `report_card_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `streams`
--
ALTER TABLE `streams`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teacher_allocations`
--
ALTER TABLE `teacher_allocations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_registers`
--
ALTER TABLE `attendance_registers`
  ADD CONSTRAINT `fk_attendance_register_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_register_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_register_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_register_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_attendance_register_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `fk_examination_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_examination_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_examination_type` FOREIGN KEY (`examination_type_id`) REFERENCES `examination_types` (`id`),
  ADD CONSTRAINT `fk_examination_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examination_classes`
--
ALTER TABLE `examination_classes`
  ADD CONSTRAINT `fk_exam_class_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exam_class_examination` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exam_class_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `examination_subjects`
--
ALTER TABLE `examination_subjects`
  ADD CONSTRAINT `fk_exam_subject_class` FOREIGN KEY (`examination_class_id`) REFERENCES `examination_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exam_subject_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fk_payment_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`),
  ADD CONSTRAINT `fk_payment_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fk_fee_structure_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`),
  ADD CONSTRAINT `fk_fee_structure_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_fee_structure_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `fk_fee_structure_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `pupil_attendance`
--
ALTER TABLE `pupil_attendance`
  ADD CONSTRAINT `fk_pupil_attendance_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pupil_attendance_register` FOREIGN KEY (`attendance_register_id`) REFERENCES `attendance_registers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pupil_invoices`
--
ALTER TABLE `pupil_invoices`
  ADD CONSTRAINT `fk_invoice_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_invoice_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`),
  ADD CONSTRAINT `fk_invoice_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `fk_invoice_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `pupil_marks`
--
ALTER TABLE `pupil_marks`
  ADD CONSTRAINT `fk_pupil_mark_exam_subject` FOREIGN KEY (`examination_subject_id`) REFERENCES `examination_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pupil_mark_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pupil_mark_user` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pupil_payments`
--
ALTER TABLE `pupil_payments`
  ADD CONSTRAINT `fk_pupil_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `fk_pupil_payment_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`),
  ADD CONSTRAINT `fk_pupil_payment_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `fk_pupil_payment_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `report_card_comments`
--
ALTER TABLE `report_card_comments`
  ADD CONSTRAINT `fk_report_exam_class` FOREIGN KEY (`examination_class_id`) REFERENCES `examination_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_next_class` FOREIGN KEY (`next_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_report_pupil` FOREIGN KEY (`pupil_id`) REFERENCES `pupils` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `streams`
--
ALTER TABLE `streams`
  ADD CONSTRAINT `fk_stream_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_allocations`
--
ALTER TABLE `teacher_allocations`
  ADD CONSTRAINT `fk_teacher_allocation_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_allocation_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_allocation_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teacher_allocation_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teacher_allocation_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_allocation_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `fk_term_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
