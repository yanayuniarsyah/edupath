-- ==============================================================================
-- Migration: Phase 10 — Shared Product Catalog & Unified Programs
-- PRD: EDUPATH UNIFIED PRODUCT ARCHITECTURE (v1.1, Ratified)
-- Scope: TKA SD, TKA SMP, TKA SMA, SNPMB/UTBK, SNPMB Alumni across Online, Offline, Hybrid
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. MASTER ACADEMIC PROGRAMS
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `target_level` ENUM('SD', 'SMP', 'SMA', 'ALUMNI') NOT NULL DEFAULT 'SMA',
  `subject_config` JSON NULL COMMENT 'Konfigurasi mata pelajaran pilihan atau peminatan versioned',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default academic programs
INSERT IGNORE INTO `academic_programs` (`id`, `code`, `name`, `description`, `target_level`, `status`) VALUES
('prog-tka-sd', 'TKA_SD', 'TKA SD', 'Program Tes Kemampuan Akademik tingkat Sekolah Dasar (Literasi & Numerasi Dasar)', 'SD', 'active'),
('prog-tka-smp', 'TKA_SMP', 'TKA SMP', 'Program Tes Kemampuan Akademik tingkat SMP (Penalaran Matematika & Literasi)', 'SMP', 'active'),
('prog-tka-sma', 'TKA_SMA', 'TKA SMA', 'Program Tes Kemampuan Akademik tingkat SMA dengan konfigurasi mata pelajaran adaptif', 'SMA', 'active'),
('prog-snpmb-utbk', 'SNPMB_UTBK', 'SNPMB/UTBK', 'Persiapan Seleksi Nasional Berdasarkan Tes PTN (TPS, Literasi B. Indonesia & Inggris, Penalaran Matematika)', 'SMA', 'active'),
('prog-snpmb-alumni', 'SNPMB_ALUMNI', 'SNPMB Alumni', 'Program intensif alumni/gap year dengan kurikulum pendalaman materi & mentoring khusus', 'ALUMNI', 'active');

-- 2. EXTEND PLANS (CENTRAL PRODUCT PACKAGES)
ALTER TABLE `plans`
  ADD COLUMN IF NOT EXISTS `program_id` VARCHAR(36) NULL AFTER `tenant_id`,
  ADD COLUMN IF NOT EXISTS `academic_program` VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK' AFTER `program_id`,
  ADD COLUMN IF NOT EXISTS `package_code` VARCHAR(50) NULL AFTER `academic_program`,
  ADD COLUMN IF NOT EXISTS `delivery_mode` ENUM('online', 'offline', 'hybrid') NOT NULL DEFAULT 'online' AFTER `package_code`,
  ADD COLUMN IF NOT EXISTS `components` JSON NULL COMMENT 'Komponen: digital_materials, practice, cbt_tryout, class_enrollment, attendance, mentoring' AFTER `features`,
  ADD COLUMN IF NOT EXISTS `base_price` DECIMAL(15,2) NULL AFTER `price`,
  ADD COLUMN IF NOT EXISTS `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR' AFTER `base_price`,
  ADD COLUMN IF NOT EXISTS `duration_value` INT NULL AFTER `duration`,
  ADD COLUMN IF NOT EXISTS `duration_unit` ENUM('days', 'months') NOT NULL DEFAULT 'days' AFTER `duration_value`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published' AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `version` INT NOT NULL DEFAULT 1 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `effective_from` DATETIME NULL AFTER `version`,
  ADD COLUMN IF NOT EXISTS `effective_until` DATETIME NULL AFTER `effective_from`;

-- Update existing plans with baseline sync
UPDATE `plans` SET `base_price` = `price` WHERE `base_price` IS NULL;
UPDATE `plans` SET `duration_value` = `duration` WHERE `duration_value` IS NULL;
UPDATE `plans` SET `program_id` = 'prog-snpmb-utbk' WHERE `program_id` IS NULL AND `academic_program` = 'SNPMB/UTBK';

-- 3. TENANT PRODUCT PRICING OVERRIDES (AC-01, AC-02 compliance)
-- Cabang/tenant TIDAK BOLEH mengubah katalog dasar; jika ada harga lokal harus melalui override eksplisit ini.
CREATE TABLE IF NOT EXISTS `tenant_product_overrides` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `tenant_id` VARCHAR(36) NOT NULL,
  `plan_id` VARCHAR(36) NOT NULL,
  `override_price` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
  `reason` VARCHAR(255) NOT NULL,
  `authorized_by` VARCHAR(36) NOT NULL,
  `status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
  `effective_from` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `effective_until` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_tenant_plan_override` (`tenant_id`, `plan_id`),
  KEY `tpo_tenant_fk` (`tenant_id`),
  KEY `tpo_plan_fk` (`plan_id`),
  CONSTRAINT `tpo_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tpo_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
