-- Phase 9: EduPath TKA SMA Assessment Expansion Migration
-- Additive & Backward-Compatible Migration for Multi-Program Assessments (SNBT, TKA Saintek, TKA Soshum)

START TRANSACTION;

-- 1. Tambah kolom track pada tabel questions & materials
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`;
ALTER TABLE `materials` ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`;

-- 2. Tambah konteks asesmen pada quiz_attempts
ALTER TABLE `quiz_attempts` 
    ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `quiz_type`,
    ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`,
    ADD COLUMN IF NOT EXISTS `package_id` VARCHAR(100) DEFAULT NULL AFTER `track`;

-- 3. Tambah konteks asesmen pada quiz_results
ALTER TABLE `quiz_results` 
    ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `quiz_type`,
    ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`,
    ADD COLUMN IF NOT EXISTS `package_id` VARCHAR(100) DEFAULT NULL AFTER `track`;

-- 4. Tambah konteks asesmen pada progress
ALTER TABLE `progress`
    ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `subtes`,
    ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`,
    ADD COLUMN IF NOT EXISTS `subject` VARCHAR(100) DEFAULT NULL AFTER `track`;

-- 5. Tambah kolom konteks pada staging impor soal
ALTER TABLE `question_imports_staging`
    ADD COLUMN IF NOT EXISTS `education_level` VARCHAR(50) DEFAULT 'SMA' AFTER `sub_materi`,
    ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `education_level`,
    ADD COLUMN IF NOT EXISTS `track` VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`,
    ADD COLUMN IF NOT EXISTS `subject` VARCHAR(100) DEFAULT NULL AFTER `track`,
    ADD COLUMN IF NOT EXISTS `domain` VARCHAR(100) DEFAULT NULL AFTER `subject`;

-- 6. Backfill existing records dengan nilai default 'umum' dan 'SNBT'
UPDATE `questions` SET `track` = 'umum' WHERE `track` IS NULL OR `track` = '';
UPDATE `materials` SET `track` = 'umum' WHERE `track` IS NULL OR `track` = '';
UPDATE `quiz_attempts` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = '';
UPDATE `quiz_results` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = '';
UPDATE `progress` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = '';

-- 7. Composite Index untuk filter performa tinggi
CREATE INDEX IF NOT EXISTS `idx_questions_taxonomy_lookup` 
    ON `questions` (`assessment_type`, `track`, `subject`, `is_active`, `is_qc_passed`);

COMMIT;
