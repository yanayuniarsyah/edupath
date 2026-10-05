-- Phase 7: SNBT Content Architecture & Taxonomy Migration
-- Compatible with CBT/CAT Question Item Specification

CREATE TABLE IF NOT EXISTS `materials` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `exam` VARCHAR(50) DEFAULT 'SNBT',
  `test_component` VARCHAR(50) DEFAULT 'TPS',
  `subtest` VARCHAR(100) NOT NULL,
  `topic` VARCHAR(100) DEFAULT NULL,
  `subtopic` VARCHAR(100) DEFAULT NULL,
  `skill` VARCHAR(150) DEFAULT NULL,
  `indicator` VARCHAR(255) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `teacher_name` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `mat_subtest_idx` (`subtest`),
  KEY `mat_topic_idx` (`topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `questions`
ADD COLUMN IF NOT EXISTS `exam` VARCHAR(50) DEFAULT 'SNBT',
ADD COLUMN IF NOT EXISTS `test_component` VARCHAR(50) DEFAULT 'TPS',
ADD COLUMN IF NOT EXISTS `subtest` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `topic` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `subtopic` VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `skill` VARCHAR(150) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `indicator` VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `question_type` VARCHAR(50) DEFAULT 'multiple_choice';

UPDATE `questions` 
SET `subtest` = COALESCE(`subtest`, `sub_materi`, `subtes`, 'Penalaran Umum')
WHERE `subtest` IS NULL OR `subtest` = '';

UPDATE `questions` 
SET `test_component` = CASE 
    WHEN `subtest` LIKE '%Literasi%' OR `subtest` LIKE '%Matematika%' THEN 'TES LITERASI'
    ELSE 'TPS'
END
WHERE `test_component` IS NULL OR `test_component` = '';
