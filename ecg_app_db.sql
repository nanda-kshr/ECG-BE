-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 28, 2025 at 09:31 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecg_app_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctor_pg`
--

CREATE TABLE `doctor_pg` (
  `id` int NOT NULL,
  `d_id` int NOT NULL,
  `pg_id` int NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `doctor_pg`
--

INSERT INTO `doctor_pg` (`id`, `d_id`, `pg_id`, `created_at`) VALUES
(1, 21, 36, '2025-11-28');

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster`
--

CREATE TABLE `duty_roster` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `duty_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ecg_images`
--

CREATE TABLE `ecg_images` (
  `id` int NOT NULL,
  `patient_id` int DEFAULT NULL,
  `technician_id` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `task_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `ecg_images` (
  `id` int NOT NULL,
  `patient_id` int DEFAULT NULL,
  `technician_id` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `task_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ecg_images`
--

INSERT INTO `ecg_images` (`id`, `patient_id`, `technician_id`, `image_path`, `image_name`, `file_size`, `mime_type`, `created_at`, `task_id`, `comment`, `status`) VALUES
(28, 22, 18, 'uploads/ecg_images/TASK56_1763705526_1.jpg', 'scaled_035fb9f1-330f-4437-9309-d5a4c29e833c8084447355595473390.jpg', 3471800, 'image/jpeg', '2025-11-21 06:12:06', 56, 'Normal', 'completed'),
(29, 22, 18, 'uploads/ecg_images/TASK57_1763705583_1.jpg', 'scaled_b0f89e03-104d-40e1-b006-7f791e40cbb83725854428879456468.jpg', 3538494, 'image/jpeg', '2025-11-21 06:13:03', 57, NULL, 'pending'),
(30, 23, 22, 'uploads/ecg_images/TASK58_1763706270_1.jpg', 'scaled_785bbe51-7cef-4438-bfd1-a1a01a3daee27312068843366594827.jpg', 4424440, 'image/jpeg', '2025-11-21 06:24:30', 58, 'Normal', 'completed'),
(31, 24, 22, 'uploads/ecg_images/TASK59_1763710520_1.jpg', 'scaled_e71f3287-b9ae-4d07-a5e9-5404537b66d7651795540628917380.jpg', 1333446, 'image/jpeg', '2025-11-21 07:35:20', 59, 'it\'s normal', 'completed'),
(32, 24, 22, 'uploads/ecg_images/TASK59_1763710520_2.jpg', 'scaled_7a996556-7000-4b76-9642-3d9cfc0169a22930688167658237968.jpg', 1494692, 'image/jpeg', '2025-11-21 07:35:20', 59, NULL, 'pending'),
(33, 24, 22, 'uploads/ecg_images/TASK60_1764125222_1.jpg', 'scaled_6dd1c12f-aedb-4c5f-9346-2a00ec433e2c5993486325919308847.jpg', 30614, 'image/jpeg', '2025-11-26 02:47:02', 60, NULL, 'pending'),
(34, 25, 23, 'uploads/ecg_images/TASK61_1764139981_1.jpg', 'scaled_a1b82782-e851-47a1-8c02-eb10601f33335534537707467294332.jpg', 1685256, 'image/jpeg', '2025-11-26 06:53:01', 61, NULL, 'pending'),
(35, 26, 23, 'uploads/ecg_images/TASK62_1764140405_1.jpg', 'scaled_998ea7d5-a4f4-4119-8c6e-47e0c503a0211489334563779141945.jpg', 1246310, 'image/jpeg', '2025-11-26 07:00:05', 62, 'normal', 'completed'),
(36, 22, 23, 'uploads/ecg_images/TASK63_1764140996_1.jpg', 'scaled_0088a21e-9d19-4e3f-aab9-19be6a07f7fc2276431522477815840.jpg', 1744735, 'image/jpeg', '2025-11-26 07:09:56', 63, NULL, 'pending'),
(37, 24, 19, 'uploads/ecg_images/TASK64_1764307979_1.jpg', 'scaled_5f6abbb1-3aad-4cab-a24d-c80976815e6a3363824539485025676.jpg', 2016586, 'image/jpeg', '2025-11-28 05:32:59', 64, NULL, 'pending'),
(38, 24, 19, 'uploads/ecg_images/TASK65_1764308056_1.jpg', 'scaled_6f68dd28-e090-4760-92c1-5e7298b1bb234715305991976673614.jpg', 2023899, 'image/jpeg', '2025-11-28 05:34:16', 65, NULL, 'pending'),
(39, 23, 19, 'uploads/ecg_images/TASK66_1764310882_1.jpg', 'scaled_8ac84e19-0146-4124-977b-140bafd149a21734690461061972735.jpg', 2784927, 'image/jpeg', '2025-11-28 06:21:22', 66, 'not Normal', 'completed'),
(40, 23, 22, 'uploads/ecg_images/TASK67_1764314620_1.jpg', 'scaled_d74f6e8c-5e0a-4b5b-aa29-eda3dd1bda6c7856263090434568421.jpg', 2978565, 'image/jpeg', '2025-11-28 07:23:40', 67, 'hi', 'completed'),
(41, 23, 22, 'uploads/ecg_images/TASK68_1764314901_1.jpg', 'scaled_ee28ad85-2c20-4e63-a799-82abc651c86b2183365266358657223.jpg', 2925195, 'image/jpeg', '2025-11-28 07:28:21', 68, 'ok ok', 'completed'),
(42, 23, 22, 'uploads/ecg_images/TASK69_1764315672_1.jpg', 'scaled_1739fd91-85cb-4496-9f1b-3b5ec422bb593839522800430709948.jpg', 2255696, 'image/jpeg', '2025-11-28 07:41:12', 69, 'do', 'completed'),
(43, 23, 22, 'uploads/ecg_images/TASK70_1764315810_1.jpg', 'scaled_bb04bb02-160b-4a0a-95b2-5614c67ff9066965757247581195338.jpg', 2303303, 'image/jpeg', '2025-11-28 07:43:30', 70, 'helo', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int NOT NULL,
  `patient_id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_doctor_id` int DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_id`, `name`, `age`, `gender`, `assigned_doctor_id`, `status`, `created_at`, `phone`) VALUES
(21, 'PAT20251121001', 'Vinay', NULL, 'male', NULL, 'pending', '2025-11-21 05:22:25', '+9188474747'),
(22, 'PAT20251121002', 'Vinay', NULL, 'male', 21, 'in_progress', '2025-11-21 05:29:20', '+911235678990'),
(23, 'PAT20251121003', 'Nandakishore P', NULL, 'male', 21, 'in_progress', '2025-11-21 06:24:06', '+918846362514'),
(24, 'PAT20251121004', 'rama', NULL, 'male', 21, 'in_progress', '2025-11-21 07:34:45', '+91123456789'),
(25, 'PAT20251126001', '133', NULL, 'male', 21, 'in_progress', '2025-11-26 06:52:34', '+91244567778'),
(26, 'PAT20251126002', '13345', NULL, 'male', 21, 'in_progress', '2025-11-26 06:59:18', '+9135678'),
(27, 'PAT20251126003', 'Tameha456455555ghgh', NULL, 'male', NULL, 'pending', '2025-11-26 07:08:23', '+91123467809');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `technician_id` int DEFAULT NULL,
  `assigned_doctor_id` int DEFAULT NULL,
  `status` enum('pending','assigned','in_progress','completed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal',
  `technician_notes` text COLLATE utf8mb4_general_ci,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comment` text COLLATE utf8mb4_general_ci,
  `assigned_pg_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `patient_id`, `technician_id`, `assigned_doctor_id`, `status`, `priority`, `technician_notes`, `assigned_at`, `created_at`, `comment`, `assigned_pg_id`) VALUES
(56, 22, 18, 16, 'pending', 'normal', '', '2025-11-21 11:42:06', '2025-11-21 06:12:06', NULL, NULL),
(57, 22, 18, 16, 'pending', 'normal', '', '2025-11-21 11:43:03', '2025-11-21 06:13:03', 'Normal', NULL),
(58, 23, 22, 21, 'pending', 'normal', 'Please verify', '2025-11-21 11:54:30', '2025-11-21 06:24:30', 'Normal', NULL),
(59, 24, 22, 21, 'pending', 'normal', '', '2025-11-21 13:05:20', '2025-11-21 07:35:20', 'it\'s normal', NULL),
(60, 24, 22, 21, 'pending', 'normal', '', '2025-11-26 08:17:02', '2025-11-26 02:47:02', NULL, NULL),
(61, 25, 23, 21, 'pending', 'normal', '12356', '2025-11-26 12:23:01', '2025-11-26 06:53:01', NULL, NULL),
(62, 26, 23, 21, 'pending', 'normal', 'weryuiop', '2025-11-26 12:30:05', '2025-11-26 07:00:05', 'normal', NULL),
(63, 22, 23, 21, 'pending', 'normal', 'hdkgkgkfjfjfncngnfjfjfndhfhfjffjdjfjfjfnfbdhdhdhdhdhdhfhdhdhfhfjfjcjxjfhxbfhfhfhfhfhdhfhffbfhdhdhfjfbfhfhfhfhfjfhdgshdhdhsgshshdgdvdhdhrhdhyrhhdhshdhdhdhddhjfhdjf', '2025-11-26 12:39:56', '2025-11-26 07:09:56', NULL, NULL),
(64, 24, 19, 21, 'pending', 'normal', '', '2025-11-28 11:02:59', '2025-11-28 05:32:59', NULL, NULL),
(65, 24, 19, 21, 'pending', 'normal', '', '2025-11-28 11:04:16', '2025-11-28 05:34:16', NULL, NULL),
(66, 23, 19, 21, 'pending', 'normal', 'very normal', '2025-11-28 11:51:22', '2025-11-28 06:21:22', 'not Normal', NULL),
(67, 23, 22, 21, 'pending', 'normal', '', '2025-11-28 12:53:40', '2025-11-28 07:23:40', NULL, NULL),
(68, 23, 22, 21, 'pending', 'normal', 'Very critical', '2025-11-28 13:01:17', '2025-11-28 07:28:21', 'ok ok', 25),
(69, 23, 22, 21, 'pending', 'normal', 'Notes', '2025-11-28 13:11:12', '2025-11-28 07:41:12', NULL, NULL),
(70, 23, 22, 21, 'pending', 'normal', '', '2025-11-28 13:13:30', '2025-11-28 07:43:30', 'helo', 30);

-- --------------------------------------------------------

--
-- Table structure for table `task_history`
--

CREATE TABLE `task_history` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `changed_by` int DEFAULT NULL,
  `old_status` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_status` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_history`
--

INSERT INTO `task_history` (`id`, `task_id`, `changed_by`, `old_status`, `new_status`, `comment`, `created_at`) VALUES
(125, 56, 18, NULL, 'pending', 'Task created manually', '2025-11-21 06:12:06'),
(126, 56, 18, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-21 06:12:06'),
(127, 57, 18, NULL, 'pending', 'Task created manually', '2025-11-21 06:13:03'),
(128, 57, 18, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-21 06:13:03'),
(129, 57, 16, NULL, 'image_completed', 'Feedback: Normal', '2025-11-21 06:14:59'),
(130, 58, 22, NULL, 'pending', 'Task created manually', '2025-11-21 06:24:30'),
(131, 58, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-21 06:24:30'),
(132, 58, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-21 06:26:56'),
(133, 58, 21, NULL, 'image_completed', 'Feedback: Normal', '2025-11-21 06:26:56'),
(134, 59, 22, NULL, 'pending', 'Task created manually', '2025-11-21 07:35:20'),
(135, 59, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-21 07:35:20'),
(136, 59, 21, NULL, 'image_completed', 'Feedback: it\'s normal', '2025-11-21 07:36:18'),
(137, 60, 22, NULL, 'pending', 'Task created manually', '2025-11-26 02:47:02'),
(138, 60, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-26 02:47:02'),
(139, 61, 23, NULL, 'pending', 'Task created manually', '2025-11-26 06:53:01'),
(140, 61, 23, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-26 06:53:01'),
(141, 61, 21, NULL, 'updated', 'Task updated', '2025-11-26 06:56:39'),
(142, 62, 23, NULL, 'pending', 'Task created manually', '2025-11-26 07:00:05'),
(143, 62, 23, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-26 07:00:05'),
(144, 62, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-26 07:01:16'),
(145, 62, 21, NULL, 'image_completed', 'Feedback: normal', '2025-11-26 07:01:16'),
(146, 63, 23, NULL, 'pending', 'Task created manually', '2025-11-26 07:09:56'),
(147, 63, 23, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-26 07:09:56'),
(148, 64, 19, NULL, 'pending', 'Task created manually', '2025-11-28 05:32:59'),
(149, 64, 19, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 05:32:59'),
(150, 65, 19, NULL, 'pending', 'Task created manually', '2025-11-28 05:34:16'),
(151, 65, 19, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 05:34:16'),
(152, 66, 19, NULL, 'pending', 'Task created manually', '2025-11-28 06:21:22'),
(153, 66, 19, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 06:21:22'),
(154, 66, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 07:09:16'),
(155, 66, 21, NULL, 'image_completed', 'Feedback: not Normal', '2025-11-28 07:09:16'),
(156, 67, 22, NULL, 'pending', 'Task created manually', '2025-11-28 07:23:40'),
(157, 67, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 07:23:40'),
(158, 68, 22, NULL, 'pending', 'Task created manually', '2025-11-28 07:28:21'),
(159, 68, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 07:28:21'),
(160, 68, 21, NULL, NULL, 'PG assigned to task: user_id=25', '2025-11-28 07:31:10'),
(161, 68, 21, NULL, NULL, 'PG assigned to task: user_id=25', '2025-11-28 07:31:17'),
(162, 68, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 07:38:49'),
(163, 68, 21, NULL, 'image_completed', 'Feedback: ok ok', '2025-11-28 07:38:49'),
(164, 69, 22, NULL, 'pending', 'Task created manually', '2025-11-28 07:41:12'),
(165, 69, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 07:41:12'),
(166, 70, 22, NULL, 'pending', 'Task created manually', '2025-11-28 07:43:30'),
(167, 70, 22, NULL, 'assigned', 'Auto-assigned duty doctor', '2025-11-28 07:43:30'),
(168, 70, 22, NULL, 'assigned', 'Auto-assigned duty PG', '2025-11-28 07:43:30'),
(169, 70, 30, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:01:52'),
(170, 70, 30, NULL, 'image_completed', 'Feedback: hi', '2025-11-28 08:01:52'),
(171, 70, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:07:28'),
(172, 70, 21, NULL, 'image_completed', 'Feedback: do', '2025-11-28 08:07:28'),
(173, 70, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:10:02'),
(174, 70, 21, NULL, 'image_completed', 'Feedback: hi', '2025-11-28 08:10:02'),
(175, 70, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:13:43'),
(176, 70, 21, NULL, 'image_completed', 'Feedback: hi', '2025-11-28 08:13:43'),
(177, 70, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:13:48'),
(178, 70, 21, NULL, 'image_completed', 'Feedback: ghi', '2025-11-28 08:13:48'),
(179, 70, 21, NULL, 'completed', 'All last 10 images commented and completed', '2025-11-28 08:13:52'),
(180, 70, 21, NULL, 'image_completed', 'Feedback: helo', '2025-11-28 08:13:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `is_duty` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','doctor','technician','pg') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'technician'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `is_duty`, `created_at`, `password_hash`, `role`) VALUES
(1, 'Admin User', 'admin@example.com', 0, '2025-11-10 07:21:36', '$2y$10$T.Og.1aiccB5LwJDnvGPGOZfRObzk0VNPC3ACbgE6vNGl17dMmUWG', 'admin'),
(2, 'Test User', 'user@example.com', 0, '2025-11-12 05:28:23', '$2y$10$YyEG/rqNIjkxryA7duYCmuT0Nhylr59bqMOr/l/PS3aCR0qNW7Aoe', 'technician'),
(5, 'Admin User', 'admin@exampldde.com', 0, '2025-11-12 05:36:36', '$2y$10$24wwaGolS7ZRJdM2Fp/OOOiPOuL4x35Pijex5Sk0c7SXPqCLmS4uu', 'admin'),
(8, 'Dr. Example', 'dr@example.com', 0, '2025-11-12 05:37:09', '$2y$10$ysPwPNprLoP2oolIIeSxV.YgUI.pJ1s80ouM5lFLdTxR0AQXT3CDe', 'doctor'),
(11, 'Tech One', 'tech1@example.com', 0, '2025-11-12 06:42:41', '$2y$10$Tt2cKDWNDQxlDblaiZTX6eaKmavgrranGKm7nYGAORsvRjPrujyQG', 'technician'),
(12, 'New Tech', 'newtech@example.com', 0, '2025-11-12 06:44:41', '$2y$10$YXkWcEKaNKdBvi8JZ3E/9.nSgh7m7o3DKpTEPDjlgu39LWpGCbaxS', 'technician'),
(13, 'New dr', 'newdr@example.com', 0, '2025-11-12 06:44:53', '$2y$10$PrP3/ugBcraaKHrVFb0czOhumXzEcs5gL/67imI02PXdO1S5RgTje', 'doctor'),
(14, 'Dr. Example', 'dr1@example.com', 0, '2025-11-12 06:47:47', '$2y$10$Mkr5m3MwPoOgNbOf5TdEwu5p8gtnxUV4NA1/660zmo1wTtiCLPdYa', 'doctor'),
(15, 'tech@tech.com', 'tech@tech.com', 0, '2025-11-12 07:50:07', '$2y$10$C6crjdnVPHwJmjgLPVDwnuQhCv3BoEuaSiO44oS5t1HW.kqFC752u', 'technician'),
(16, 'Doc Nitesh', 'doctor@example.com', 0, '2025-11-17 07:49:08', '$2y$10$pI7wsq/H3fZ2cx6LF44yoefj5n025he2GzAr3rsReD6SeFkakhGyS', 'doctor'),
(17, 'Technician Nitesh', 'technician@example.com', 0, '2025-11-17 07:49:47', '$2y$10$K7AI0DrSfKvIXg6z7teifukpi5mQ6i60MVTgCwx552yNcOyP49WOG', 'technician'),
(18, 'Technician Nitesh', 'tech@example.com', 0, '2025-11-17 07:50:36', '$2y$10$rriwAv.05tuDkv8YDsaWzuXZX7zpjbsn9TWwbKSoP3fPN0SEUdF1O', 'technician'),
(19, 'Clinic Doctor', 'clinic@doctor.com', 0, '2025-11-17 08:44:18', '$2y$10$qVIS01awHgC5wZSfLMdAI.hZNsbGLwb9gzEqMCoJQ/1tEo.CIUPj2', 'technician'),
(20, 'Nandakishore', 'doctordr@dr.com', 0, '2025-11-20 09:08:34', '$2y$10$YtGTy4A132vxNiivcpPtmuBSimRNOh68GamtWCcvu5krRnOpLylVO', 'technician'),
(21, 'Dr Sathish', 'sathish@doc.com', 1, '2025-11-21 06:17:33', '$2y$10$BXi.FLBD5qakAOjpjoAad.gjxFqrruskOhy7tYTWvH/k.Kx950B5S', 'doctor'),
(22, 'Doc Dinakar', 'doc@clinic.com', 0, '2025-11-21 06:22:17', '$2y$10$OhGlyI7PzDCsEre3veb2QOIGMl4aL2hVCuEfxCrUXPcYYpKCTB/1.', 'technician'),
(23, 'Doc Rama', 'rama@clinicaldoctor.com', 0, '2025-11-26 04:13:11', '$2y$10$fuIt4DPUySG1MsZxZI6RM.d3sKC8ru3vkDJo/ul99mOcjA4NToBf2', 'technician'),
(24, 'Pg', 'pg@example.com', 0, '2025-11-27 08:44:17', '$2y$10$sQGImhY50rn.4qLEy0OTLOU4PTPc43PU.41SyWb7UCdIULwBRYFdC', 'pg'),
(25, 'billy', 'billy@example.com', 0, '2025-11-27 08:44:45', '$2y$10$ZQuPBs57TfWKaaznpaHRbucIquurNq4hyYgNr0mn29.NANZVmnSoK', 'pg'),
(26, 'ram', 'ram@ram.com', 0, '2025-11-28 07:36:41', '$2y$10$vczqm7Dq5yWT0VSbGG81QeOD47XpWLnO1lyNOmDnU0Ja6pyZnbkHe', 'pg'),
(27, 'Ravi', 'ravi@ravi.com', 0, '2025-11-28 07:36:56', '$2y$10$WBGkdPVgV/gRMJJFWzGWv.546gabGWWTsifvMKIdP64Qx2y71cvX6', 'pg'),
(28, 'reynis', 'reynis@reynjs.com', 0, '2025-11-28 07:37:22', '$2y$10$XZZ/yypYxK0IK/sqD3DNVeLmR6C8RXVIN6BzV5hbT5NM45adVWFZO', 'pg'),
(29, 'PG doc', 'pg@df.xom', 0, '2025-11-28 07:37:35', '$2y$10$JVbtc.z81xEqVAgXUlIgtOS2KeOaYHd23G3W9yQ2w3S7LCiETlrhe', 'pg'),
(30, 'Syam', 'pgs@example.com', 1, '2025-11-28 07:40:27', '$2y$10$V3wanZnGd4F1/zG8Iy4AiukGbtLLQNnaCx2Fvfs4g8TZNFv6SkWB6', 'pg'),
(31, 'Dinesh', 'din@example.com', 0, '2025-11-28 08:23:26', '$2y$10$N41Pc8mhupeO2mBYN4qNs.AG10SC4QiiioqHWI1HVsrTXm6pmy4Du', 'pg'),
(32, 'hii', 'fii@gii.dodm', 0, '2025-11-28 08:26:34', '$2y$10$5YmKik.QmhEkokeVKQbveuR6jszhRydRMl7PV.j.eKplQvHSUT53a', 'pg'),
(33, 'hfudd', 'whj@jdjd.did', 0, '2025-11-28 08:27:09', '$2y$10$nJb04F.N6hmf9fiAiSY8f.J6xqyxHYFI7a6zTGm2kd4rfrgW9WGge', 'pg'),
(34, 'dheiej', 'hsjsj@dhjd.xkdj', 0, '2025-11-28 08:34:01', '$2y$10$EPfU9PAMJgJN7Kvm3u0nTueTeixQJwuYN7NMIJ/PHdFCTenbW/AXe', 'pg'),
(35, 'John Doe PG', 'john.pg@example.com', 0, '2025-11-28 08:35:25', '$2y$10$t4jXRybv20wSHvfi2R8MIO4sVrw6VTlJZke79q09oECjdFTEuEr3C', 'pg'),
(36, 'Johdn Doe PG', 'johdn.pg@example.com', 0, '2025-11-28 08:35:48', '$2y$10$X8cPv7BGJVX5goy6wVAkm.wtiqPCZuIKPDVbjqoCrEga7Hg1sU2he', 'pg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doctor_pg`
--
ALTER TABLE `doctor_pg`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `duty_roster`
--
ALTER TABLE `duty_roster`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_duty_user` (`user_id`),
  ADD KEY `idx_duty_date` (`duty_date`);

--
-- Indexes for table `ecg_images`
--
ALTER TABLE `ecg_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ecg_images_patient` (`patient_id`),
  ADD KEY `fk_ecg_images_tech` (`technician_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patient_patientid` (`patient_id`),
  ADD KEY `idx_patients_assigned_doctor` (`assigned_doctor_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tasks_patient` (`patient_id`),
  ADD KEY `idx_tasks_assigned_doctor` (`assigned_doctor_id`),
  ADD KEY `fk_tasks_technician` (`technician_id`);

--
-- Indexes for table `task_history`
--
ALTER TABLE `task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_history_task` (`task_id`),
  ADD KEY `fk_task_history_user` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_is_duty` (`is_duty`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctor_pg`
--
ALTER TABLE `doctor_pg`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `duty_roster`
--
ALTER TABLE `duty_roster`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ecg_images`
--
ALTER TABLE `ecg_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `duty_roster`
--
ALTER TABLE `duty_roster`
  ADD CONSTRAINT `fk_duty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ecg_images`
--
ALTER TABLE `ecg_images`
  ADD CONSTRAINT `fk_ecg_images_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ecg_images_tech` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_assigned_doctor` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_assigned_doctor` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tasks_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tasks_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_history`
--
ALTER TABLE `task_history`
  ADD CONSTRAINT `fk_task_history_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
