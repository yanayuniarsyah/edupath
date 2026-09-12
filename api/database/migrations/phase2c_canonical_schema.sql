-- EduPath Canonical Assessment Schema (Phase 2C)
-- DO NOT EXECUTE AUTOMATICALLY. MySQL Runtime is BLOCKED. 
-- Schema must be reviewed and executed manually when DB is online.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `assessments` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `slug` varchar(100) NOT NULL UNIQUE,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assessment_versions` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `assessment_id` varchar(36) NOT NULL,
  `version` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `duration_sec` int(11) DEFAULT 0,
  `config` json DEFAULT NULL,
  `published_at` timestamp NULL,
  CONSTRAINT `av_assessment_fk` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `questions_canonical` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `question_revisions` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `question_id` varchar(36) NOT NULL,
  `revision_number` int(11) NOT NULL,
  `content` text NOT NULL,
  `options` json NOT NULL,
  `answer_key` json NOT NULL,
  `default_weight` decimal(8,2) DEFAULT 1.00,
  `explanation` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_qr_revision` (`question_id`, `revision_number`),
  CONSTRAINT `qr_question_fk` FOREIGN KEY (`question_id`) REFERENCES `questions_canonical` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assessment_questions` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `assessment_version_id` varchar(36) NOT NULL,
  `question_revision_id` varchar(36) NOT NULL,
  `seq_order` int(11) NOT NULL,
  `weight_override` decimal(8,2) DEFAULT NULL,
  UNIQUE KEY `uk_av_q` (`assessment_version_id`, `question_revision_id`),
  UNIQUE KEY `uk_av_seq` (`assessment_version_id`, `seq_order`),
  CONSTRAINT `aq_version_fk` FOREIGN KEY (`assessment_version_id`) REFERENCES `assessment_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aq_revision_fk` FOREIGN KEY (`question_revision_id`) REFERENCES `question_revisions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attempts` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `student_id` varchar(36) NOT NULL,
  `assessment_version_id` varchar(36) NOT NULL,
  `status` varchar(20) DEFAULT 'started',
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL,
  `completed_at` timestamp NULL,
  CONSTRAINT `att_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `att_version_fk` FOREIGN KEY (`assessment_version_id`) REFERENCES `assessment_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attempt_responses` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `attempt_id` varchar(36) NOT NULL,
  `assessment_question_id` varchar(36) NOT NULL,
  `response_value` varchar(255) NOT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score_earned` decimal(8,2) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_att_aq` (`attempt_id`, `assessment_question_id`),
  CONSTRAINT `ar_attempt_fk` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ar_aq_fk` FOREIGN KEY (`assessment_question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attempt_results` (
  `id` varchar(36) NOT NULL PRIMARY KEY,
  `attempt_id` varchar(36) NOT NULL UNIQUE,
  `total_score` decimal(8,2) DEFAULT 0.00,
  `profile_metadata` json DEFAULT NULL,
  CONSTRAINT `res_attempt_fk` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
