-- Mobile Backend Readiness Migrations
-- Phase 3: Finalized schema for EduPath Mobile App

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Refresh Tokens Table (Multi-Device Sessions)
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `device_id` VARCHAR(255) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `refresh_tokens_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Device Tokens Table (FCM)
CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `fcm_token` VARCHAR(255) NOT NULL,
  `device_id` VARCHAR(255) NOT NULL,
  `platform` VARCHAR(50) DEFAULT 'android',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_seen_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `device_tokens_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. SPP Master Data Tables (Normalized)
CREATE TABLE IF NOT EXISTS `target_universities` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `target_programs` (
  `id` VARCHAR(36) NOT NULL,
  `university_id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `target_score` INT NOT NULL,
  `req_ability` JSON NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tp_univ_fk` (`university_id`),
  CONSTRAINT `tp_univ_fk` FOREIGN KEY (`university_id`) REFERENCES `target_universities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `spp_diagnostic_questions` (
  `id` VARCHAR(36) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `skill` VARCHAR(100) NOT NULL,
  `difficulty` VARCHAR(50) DEFAULT 'Intermediate',
  `question` TEXT NOT NULL,
  `options` JSON NOT NULL,
  `answer` INT NOT NULL,
  `hint` TEXT,
  `concept` TEXT,
  `explanation` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Quiz Attempts (Session & Idempotency)
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `quiz_type` VARCHAR(50) NOT NULL,
  `subtes` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('started', 'completed') DEFAULT 'started',
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `qa_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Rate Limits Table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(100) NOT NULL,
  `attempt_count` INT DEFAULT 1,
  `lock_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `ip_endpoint` (`ip_address`, `endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
