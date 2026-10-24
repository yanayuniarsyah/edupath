-- Phase 8: Universal Assessment Taxonomy V2 Migration
-- Supports Multi-Level (SD, SMP, SMA, SMK, UMUM) and Multi-Assessment (TKA, SNBT, SPP, ASESMEN)

ALTER TABLE `questions`
ADD COLUMN IF NOT EXISTS `education_level` VARCHAR(50) DEFAULT 'SMA' AFTER `id`,
ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `education_level`,
ADD COLUMN IF NOT EXISTS `subject` VARCHAR(100) DEFAULT NULL AFTER `assessment_type`,
ADD COLUMN IF NOT EXISTS `domain` VARCHAR(100) DEFAULT NULL AFTER `subject`,
ADD COLUMN IF NOT EXISTS `cognitive_demand` VARCHAR(50) DEFAULT 'C3' AFTER `indicator`;

ALTER TABLE `materials`
ADD COLUMN IF NOT EXISTS `education_level` VARCHAR(50) DEFAULT 'SMA' AFTER `id`,
ADD COLUMN IF NOT EXISTS `assessment_type` VARCHAR(50) DEFAULT 'SNBT' AFTER `education_level`,
ADD COLUMN IF NOT EXISTS `subject` VARCHAR(100) DEFAULT NULL AFTER `assessment_type`,
ADD COLUMN IF NOT EXISTS `domain` VARCHAR(100) DEFAULT NULL AFTER `subject`;

-- Backfill existing data
UPDATE `questions`
SET `education_level` = 'SMA'
WHERE `education_level` IS NULL OR `education_level` = '';

UPDATE `questions`
SET `assessment_type` = 'SNBT'
WHERE `assessment_type` IS NULL OR `assessment_type` = '';

UPDATE `materials`
SET `education_level` = 'SMA'
WHERE `education_level` IS NULL OR `education_level` = '';

UPDATE `materials`
SET `assessment_type` = 'SNBT'
WHERE `assessment_type` IS NULL OR `assessment_type` = '';
