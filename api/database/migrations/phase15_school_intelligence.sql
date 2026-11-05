-- ==============================================================================
-- Migration: Phase 15 — EduPath School Intelligence Module
-- PRD: EDUPATH SCHOOL INTELLIGENCE (v1.2, Baseline)
-- Scope: Multi-Level Rapor (SD, SMP, SMA), Excel Import/Export, Student 360,
--        Guidance Counseling Intelligence & Admission Readiness (SNBP/SNBT)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabel Header Rapor Siswa per Semester
CREATE TABLE IF NOT EXISTS `report_cards` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `tenant_id` VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
  `school_id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `education_level` ENUM('SD', 'SMP', 'SMA') NOT NULL DEFAULT 'SMA',
  `academic_year` VARCHAR(20) NOT NULL COMMENT 'Contoh: 2026/2027',
  `semester` ENUM('ganjil', 'genap') NOT NULL DEFAULT 'ganjil',
  `class_level` VARCHAR(20) NOT NULL COMMENT 'Contoh: Kelas 4, Kelas 8, Kelas 12',
  `class_name` VARCHAR(50) NULL COMMENT 'Contoh: 12 MIPA 1, 8B',
  `curriculum` VARCHAR(50) NOT NULL DEFAULT 'Kurikulum Merdeka',
  `status` ENUM('draft', 'submitted', 'verified', 'rejected', 'locked') NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `verified_by` VARCHAR(36) NULL,
  `verified_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_student_report_period` (`student_id`, `academic_year`, `semester`),
  KEY `idx_rc_school` (`school_id`),
  KEY `idx_rc_level` (`education_level`),
  KEY `idx_rc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabel Rincian Nilai per Mata Pelajaran Rapor
CREATE TABLE IF NOT EXISTS `report_card_details` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `report_card_id` VARCHAR(36) NOT NULL,
  `subject_name` VARCHAR(100) NOT NULL,
  `subject_code` VARCHAR(50) NULL,
  `score` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `letter_grade` VARCHAR(5) NULL COMMENT 'A, B, C, D',
  `kkm` DECIMAL(5, 2) NOT NULL DEFAULT 75.00,
  `competency_achievement` TEXT NULL COMMENT 'Deskripsi Capaian Kompetensi / Catatan Kemajuan',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_rcd_card` (`report_card_id`),
  KEY `idx_rcd_subject` (`subject_name`),
  CONSTRAINT `fk_rcd_card` FOREIGN KEY (`report_card_id`) REFERENCES `report_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabel Catatan Konseling & Intervensi Guru BK (Guidance Intelligence)
CREATE TABLE IF NOT EXISTS `guidance_records` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `tenant_id` VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
  `school_id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `counselor_name` VARCHAR(150) NOT NULL,
  `category` ENUM('akademik', 'karir_studi', 'kedisiplinan', 'pribadi') NOT NULL DEFAULT 'akademik',
  `priority` ENUM('rendah', 'sedang', 'tinggi', 'kritis') NOT NULL DEFAULT 'sedang',
  `issue_summary` TEXT NOT NULL,
  `action_plan` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved', 'monitoring') NOT NULL DEFAULT 'open',
  `follow_up_date` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_gr_school` (`school_id`),
  KEY `idx_gr_student` (`student_id`),
  KEY `idx_gr_priority` (`priority`),
  KEY `idx_gr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tabel Pelacakan Pilihan Karir & Seleksi Perguruan Tinggi (Admission Tracking)
CREATE TABLE IF NOT EXISTS `admission_trackings` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `tenant_id` VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
  `school_id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `target_university` VARCHAR(150) NOT NULL,
  `target_faculty` VARCHAR(150) NULL,
  `target_major` VARCHAR(150) NOT NULL,
  `selection_path` ENUM('SNBP', 'SNBT', 'MANDIRI', 'KEDINASAN', 'LAINNYA') NOT NULL DEFAULT 'SNBT',
  `priority_order` INT NOT NULL DEFAULT 1,
  `status` ENUM('rencana', 'terdaftar', 'lolos', 'tidak_lolos', 'cadangan') NOT NULL DEFAULT 'rencana',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_at_school` (`school_id`),
  KEY `idx_at_student` (`student_id`),
  KEY `idx_at_path` (`selection_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
