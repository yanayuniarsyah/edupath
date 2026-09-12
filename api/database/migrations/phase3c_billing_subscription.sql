-- EduPath SaaS Billing & Subscription Migrations
-- Block 3: Billing & Subscription Foundation

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Create plan_entitlements mapping table
CREATE TABLE IF NOT EXISTS `plan_entitlements` (
  `id` VARCHAR(36) NOT NULL,
  `plan_id` VARCHAR(36) NOT NULL,
  `feature_key` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_feature_uq` (`plan_id`, `feature_key`),
  CONSTRAINT `pe_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create subscriptions table with Hybrid XOR Ownership
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) DEFAULT NULL,
  `student_id` VARCHAR(36) DEFAULT NULL,
  `plan_id` VARCHAR(36) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'active',
  `payment_reference` VARCHAR(100) DEFAULT NULL,
  `plan_name_snapshot` VARCHAR(100) DEFAULT NULL,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_payment_ref_uq` (`payment_reference`),
  KEY `sub_tenant_fk` (`tenant_id`),
  KEY `sub_student_fk` (`student_id`),
  CONSTRAINT `sub_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sub_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sub_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sub_ownership_chk` CHECK (
      (tenant_id IS NOT NULL AND student_id IS NULL) OR 
      (tenant_id IS NULL AND student_id IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create entitlements table with Hybrid XOR Ownership
CREATE TABLE IF NOT EXISTS `entitlements` (
  `id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) DEFAULT NULL,
  `student_id` VARCHAR(36) DEFAULT NULL,
  `feature_key` VARCHAR(100) NOT NULL,
  `subscription_id` VARCHAR(36) DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ent_owner_feature_uq` (`tenant_id`, `student_id`, `feature_key`),
  KEY `ent_sub_fk` (`subscription_id`),
  CONSTRAINT `ent_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ent_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ent_sub_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ent_ownership_chk` CHECK (
      (tenant_id IS NOT NULL AND student_id IS NULL) OR 
      (tenant_id IS NULL AND student_id IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
