-- Phase 5: Question Bank Migration (Limited Scope)

-- 1. Tambahkan kolom ke tabel questions (Non-Destructive)
ALTER TABLE `questions`
ADD COLUMN `sub_materi` VARCHAR(100) DEFAULT NULL AFTER `subtes`,
ADD COLUMN `cognitive_demand` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `source_type` VARCHAR(50) DEFAULT 'author_created',
ADD COLUMN `rights_status` VARCHAR(50) DEFAULT 'unknown',
ADD COLUMN `p_value` FLOAT DEFAULT NULL,
ADD COLUMN `point_biserial` FLOAT DEFAULT NULL,
ADD COLUMN `insufficient_data` TINYINT(1) DEFAULT 1;

-- 2. Migrasi data existing dari subtes ke sub_materi
UPDATE `questions` SET `sub_materi` = `subtes`;

-- 3. Tabel Staging untuk Batch Importer
CREATE TABLE IF NOT EXISTS `question_imports_staging` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `batch_id` VARCHAR(100) NOT NULL,
  `tenant_id` VARCHAR(36) DEFAULT NULL,
  `sub_materi` VARCHAR(100) NOT NULL,
  `difficulty` VARCHAR(20) DEFAULT 'medium',
  `question` TEXT NOT NULL,
  `option_a` TEXT NOT NULL,
  `option_b` TEXT NOT NULL,
  `option_c` TEXT NOT NULL,
  `option_d` TEXT NOT NULL,
  `option_e` TEXT DEFAULT NULL,
  `correct` VARCHAR(1) NOT NULL,
  `explanation` TEXT DEFAULT NULL,
  `cognitive_demand` VARCHAR(50) DEFAULT NULL,
  `source_type` VARCHAR(50) DEFAULT 'author_created',
  `rights_status` VARCHAR(50) DEFAULT 'unknown',
  `status` VARCHAR(20) DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
