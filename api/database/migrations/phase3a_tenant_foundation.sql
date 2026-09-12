-- EduPath SaaS Foundation Migrations
-- Block 1: Tenant Foundation & Data Isolation

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Create tenants table
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` VARCHAR(36) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insert Legacy B2C Tenant
INSERT IGNORE INTO `tenants` (`id`, `name`, `slug`) VALUES 
('553af312-fb50-4e24-93ee-0d1abd52a62d', 'EduPath B2C', 'edupath-b2c');

-- 3. Alter students table
ALTER TABLE `students`
ADD COLUMN `tenant_id` VARCHAR(36) DEFAULT '553af312-fb50-4e24-93ee-0d1abd52a62d' AFTER `id`;

-- Update existing students
UPDATE `students` SET `tenant_id` = '553af312-fb50-4e24-93ee-0d1abd52a62d' WHERE `tenant_id` IS NULL;

-- Make tenant_id NOT NULL
ALTER TABLE `students` MODIFY COLUMN `tenant_id` VARCHAR(36) NOT NULL;

-- 4. Alter admins table
ALTER TABLE `admins`
ADD COLUMN `tenant_id` VARCHAR(36) DEFAULT '553af312-fb50-4e24-93ee-0d1abd52a62d' AFTER `id`;

-- Update existing admins
UPDATE `admins` SET `tenant_id` = '553af312-fb50-4e24-93ee-0d1abd52a62d' WHERE `tenant_id` IS NULL;

-- Make tenant_id NOT NULL
ALTER TABLE `admins` MODIFY COLUMN `tenant_id` VARCHAR(36) NOT NULL;

-- 5. Add Foreign Key constraints
ALTER TABLE `students`
ADD CONSTRAINT `students_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

ALTER TABLE `admins`
ADD CONSTRAINT `admins_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

COMMIT;
