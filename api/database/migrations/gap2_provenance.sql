-- GAP 2: Provenance Metadata Expansion
ALTER TABLE `questions`
ADD COLUMN `source_name` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `source_year` INT DEFAULT NULL,
ADD COLUMN `source_reference` TEXT DEFAULT NULL;

ALTER TABLE `question_imports_staging`
ADD COLUMN `source_name` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `source_year` INT DEFAULT NULL,
ADD COLUMN `source_reference` TEXT DEFAULT NULL;
