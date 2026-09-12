-- SPP Implementation Sprint Migrations
-- Phase 2E-7: SPP Integration

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. SPP Attempts
CREATE TABLE IF NOT EXISTS `spp_attempts` (
  `id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `attempt_status` ENUM('DRAFT', 'IN_PROGRESS', 'SUBMITTED') DEFAULT 'DRAFT',
  `scoring_status` ENUM('PENDING', 'SCORED', 'INSUFFICIENT_DATA') DEFAULT 'PENDING',
  `quality_status` ENUM('ACCEPTABLE', 'QUALITY_REVIEW_REQUIRED') DEFAULT 'ACCEPTABLE',
  `duration_seconds` INT DEFAULT 0,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` DATETIME DEFAULT NULL,
  
  -- Version Tracking
  `item_bank_version` VARCHAR(50) DEFAULT 'SPP-ITEM-1.0.0',
  `scoring_version` VARCHAR(50) DEFAULT 'SPP-SCORE-1.0.0',
  `interpretation_version` VARCHAR(50) DEFAULT 'SPP-INTERPRET-1.0.0',
  `narrative_bank_version` VARCHAR(50) DEFAULT 'SPP-NARRATIVE-1.0.0',
  
  PRIMARY KEY (`id`),
  KEY `spp_student_id` (`student_id`),
  CONSTRAINT `spp_att_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. SPP Responses (Immutable Raw Data)
CREATE TABLE IF NOT EXISTS `spp_responses` (
  `id` VARCHAR(36) NOT NULL,
  `attempt_id` VARCHAR(36) NOT NULL,
  `responses` JSON NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spp_resp_attempt_uq` (`attempt_id`),
  CONSTRAINT `spp_resp_attempt_fk` FOREIGN KEY (`attempt_id`) REFERENCES `spp_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. SPP Scores
CREATE TABLE IF NOT EXISTS `spp_scores` (
  `id` VARCHAR(36) NOT NULL,
  `attempt_id` VARCHAR(36) NOT NULL,
  `facets` JSON NOT NULL,
  `dimensions` JSON NOT NULL,
  `riasec` JSON NOT NULL,
  `riasec_top3` JSON NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spp_scores_attempt_uq` (`attempt_id`),
  CONSTRAINT `spp_scores_attempt_fk` FOREIGN KEY (`attempt_id`) REFERENCES `spp_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. SPP Profiles
CREATE TABLE IF NOT EXISTS `spp_profiles` (
  `id` VARCHAR(36) NOT NULL,
  `attempt_id` VARCHAR(36) NOT NULL,
  `bands` JSON NOT NULL,
  `relative_strength` VARCHAR(50) DEFAULT NULL,
  `development_area` VARCHAR(50) DEFAULT NULL,
  `narratives` JSON NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spp_profiles_attempt_uq` (`attempt_id`),
  CONSTRAINT `spp_profiles_attempt_fk` FOREIGN KEY (`attempt_id`) REFERENCES `spp_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
