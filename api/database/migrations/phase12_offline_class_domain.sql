-- ==============================================================================
-- Migration: Phase 12 — Offline Class Domain & Session Capacity
-- PRD: EDUPATH UNIFIED PRODUCT ARCHITECTURE (v1.1, Ratified)
-- Decisions: E1-E7 (Offerings, Cohort, Atomic all_sessions Capacity, Makeup Quota)
-- Acceptance Criteria: AC-11, AC-14, AC-15
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. CLASS OFFERINGS TABLE (PRD E2, AC-11)
CREATE TABLE IF NOT EXISTS `class_offerings` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `tenant_id` VARCHAR(36) NOT NULL,
  `program_id` VARCHAR(36) NULL,
  `academic_program` VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK',
  `plan_id` VARCHAR(36) NULL,
  `class_id` VARCHAR(36) NULL COMMENT 'Cohort / Rombel terhubung',
  `name` VARCHAR(150) NOT NULL,
  `renewal_mode` ENUM('new_enrollment', 'extend_existing') NOT NULL DEFAULT 'new_enrollment',
  `max_capacity` INT NOT NULL DEFAULT 15,
  `registration_open_at` DATETIME NULL,
  `registration_close_at` DATETIME NULL,
  `status` ENUM('draft', 'open', 'closed', 'in_progress', 'completed') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `co_tenant_fk` (`tenant_id`),
  KEY `co_class_fk` (`class_id`),
  KEY `co_plan_fk` (`plan_id`),
  CONSTRAINT `co_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. EXTEND CLASSES & CLASS_STUDENTS
ALTER TABLE `classes`
  ADD COLUMN IF NOT EXISTS `offering_id` VARCHAR(36) NULL AFTER `academic_year`,
  ADD KEY IF NOT EXISTS `cls_offering_fk` (`offering_id`);

ALTER TABLE `class_students`
  ADD COLUMN IF NOT EXISTS `renewal_reference_id` VARCHAR(36) NULL COMMENT 'ID enrollment sebelumnya jika merupakan perpanjangan' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `confirmed_at` DATETIME NULL COMMENT 'Waktu kursi dikonfirmasi setelah pembayaran terverifikasi' AFTER `joined_at`;

-- 3. MAKEUP REQUESTS TABLE (PRD E7, AC-15)
CREATE TABLE IF NOT EXISTS `makeup_requests` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `student_id` VARCHAR(36) NOT NULL,
  `origin_session_id` VARCHAR(36) NOT NULL,
  `target_session_id` VARCHAR(36) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'attended') NOT NULL DEFAULT 'pending',
  `approved_by` VARCHAR(36) NULL,
  `approved_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `mr_student_fk` (`student_id`),
  KEY `mr_orig_ses_fk` (`origin_session_id`),
  KEY `mr_targ_ses_fk` (`target_session_id`),
  CONSTRAINT `mr_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mr_orig_ses_fk` FOREIGN KEY (`origin_session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mr_targ_ses_fk` FOREIGN KEY (`target_session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
