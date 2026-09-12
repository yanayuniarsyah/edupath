-- phpMyAdmin SQL Dump
-- Database: EduPath

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Table structure for table `admins`
CREATE TABLE `admins` (
  `id` varchar(36) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `students`
CREATE TABLE `students` (
  `id` varchar(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `school` varchar(150) DEFAULT NULL,
  `target_ptn` varchar(150) DEFAULT NULL,
  `plan` varchar(50) DEFAULT 'free',
  `streak` int(11) DEFAULT '0',
  `coins` int(11) DEFAULT '0',
  `total_score` float DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `progress`
CREATE TABLE `progress` (
  `id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `subtes` varchar(100) NOT NULL,
  `bab` varchar(100) NOT NULL,
  `score` float DEFAULT '0',
  `mastery` int(11) DEFAULT '0',
  `last_study` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `quiz_results`
CREATE TABLE `quiz_results` (
  `id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `quiz_type` varchar(50) NOT NULL,
  `subtes` varchar(100) DEFAULT NULL,
  `score` float DEFAULT '0',
  `correct` int(11) DEFAULT '0',
  `total` int(11) DEFAULT '0',
  `duration_sec` int(11) DEFAULT '0',
  `answers` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `questions`
CREATE TABLE `questions` (
  `id` varchar(36) NOT NULL,
  `subtes` varchar(100) NOT NULL,
  `bab` varchar(100) DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT 'medium',
  `irt_score` float DEFAULT '5',
  `question` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `option_e` text DEFAULT NULL,
  `correct` varchar(1) NOT NULL,
  `explanation` text DEFAULT NULL,
  `trick` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `plans`
CREATE TABLE `plans` (
  `id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` float NOT NULL,
  `duration` int(11) NOT NULL,
  `features` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `orders`
CREATE TABLE `orders` (
  `id` varchar(36) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `amount` float NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `snap_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `announcements`
CREATE TABLE `announcements` (
  `id` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Indexes & Primary Keys

ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_subtes_bab` (`student_id`,`subtes`,`bab`),
  ADD KEY `student_id` (`student_id`);

ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `student_id` (`student_id`);

ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

-- Constraints
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

COMMIT;

-- Tambahkan 1 Admin Default:
-- username: admin | password: admin123 (hash bcrypt)
INSERT INTO `admins` (`id`, `username`, `password`, `name`) VALUES
('admin-uuid-1234', 'admin', '$2a$10$Qx9mD9Zq/i9f3R1.4qZl7uQ.E1R8nU/iX7cM7fX9.TjKjQ.Vj.8yO', 'Administrator');
