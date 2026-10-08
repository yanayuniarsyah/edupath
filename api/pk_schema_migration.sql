-- PK RUNTIME FOUNDATION MIGRATION SCRIPT v1.0
-- Safe, Idempotent, and Reversible Structure

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. PK LEAVES
CREATE TABLE IF NOT EXISTS `pk_leaves` (
  `id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. PK CONSTRUCTS
CREATE TABLE IF NOT EXISTS `pk_constructs` (
  `id` varchar(50) NOT NULL,
  `leaf_id` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pk_constructs_leaf` FOREIGN KEY (`leaf_id`) REFERENCES `pk_leaves` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PK INDICATORS
CREATE TABLE IF NOT EXISTS `pk_indicators` (
  `id` varchar(50) NOT NULL,
  `construct_id` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pk_indicators_construct` FOREIGN KEY (`construct_id`) REFERENCES `pk_constructs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PK QUESTIONS (Content Storage)
CREATE TABLE IF NOT EXISTS `pk_questions` (
  `id` varchar(50) NOT NULL,
  `indicator_id` varchar(50) NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `difficulty` varchar(20) DEFAULT 'PROVISIONAL',
  `question` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `option_e` text DEFAULT NULL,
  `correct` varchar(1) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pk_questions_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `pk_indicators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. PK QUESTION ANALYTICS (Tutor AI Distractor Logic)
CREATE TABLE IF NOT EXISTS `pk_question_analytics` (
  `id` varchar(50) NOT NULL,
  `question_id` varchar(50) NOT NULL,
  `distractor_a_rationale` text,
  `distractor_b_rationale` text,
  `distractor_c_rationale` text,
  `distractor_d_rationale` text,
  `distractor_e_rationale` text,
  `expected_error_pattern` text,
  `misconception` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pk_analytics_question` FOREIGN KEY (`question_id`) REFERENCES `pk_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. PK DIAGNOSTIC EVIDENCE (Result Recording)
CREATE TABLE IF NOT EXISTS `pk_diagnostic_evidence` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `attempt_id` varchar(50) NOT NULL,
  `indicator_id` varchar(50) NOT NULL,
  `chosen_distractor` varchar(1) NOT NULL,
  `mapped_misconception` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
  -- Note: student_id references `students` (legacy table) implicitly or explicitly
  -- CONSTRAINT `fk_pk_evidence_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- ROLLBACK SCRIPT (For Reference/Testing)
-- START TRANSACTION;
-- DROP TABLE IF EXISTS `pk_diagnostic_evidence`;
-- DROP TABLE IF EXISTS `pk_question_analytics`;
-- DROP TABLE IF EXISTS `pk_questions`;
-- DROP TABLE IF EXISTS `pk_indicators`;
-- DROP TABLE IF EXISTS `pk_constructs`;
-- DROP TABLE IF EXISTS `pk_leaves`;
-- COMMIT;
