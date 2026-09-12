-- EduPath SaaS Affiliate & Distribution Migrations
-- Block 4: Affiliate & Commission Engine

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Create affiliates table (Tenant-Owned)
CREATE TABLE IF NOT EXISTS `affiliates` (
  `id` VARCHAR(36) NOT NULL,
  `user_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NOT NULL,
  `referral_code` VARCHAR(50) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_code_uq` (`referral_code`),
  KEY `aff_tenant_fk` (`tenant_id`),
  CONSTRAINT `aff_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aff_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add referred_by to students (Account-Level Attribution)
ALTER TABLE `students`
ADD COLUMN `referred_by` VARCHAR(36) DEFAULT NULL AFTER `tenant_id`;

ALTER TABLE `students`
ADD CONSTRAINT `std_referred_by_fk` FOREIGN KEY (`referred_by`) REFERENCES `affiliates` (`id`) ON DELETE SET NULL;

-- 3. Add affiliate_id to orders (Order Attribution Inheritance)
ALTER TABLE `orders`
ADD COLUMN `affiliate_id` VARCHAR(36) DEFAULT NULL AFTER `student_id`;

ALTER TABLE `orders`
ADD CONSTRAINT `ord_affiliate_fk` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE SET NULL;

-- 4. Create commissions table (Verified Conversions)
CREATE TABLE IF NOT EXISTS `commissions` (
  `id` VARCHAR(36) NOT NULL,
  `affiliate_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NOT NULL,
  `order_id` VARCHAR(36) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `commission_rate_snapshot` DECIMAL(5,2) NOT NULL,
  `status` VARCHAR(20) DEFAULT 'pending', -- pending settlement
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comm_order_uq` (`order_id`), -- Idempotency: maximum one commission per order
  KEY `comm_affiliate_fk` (`affiliate_id`),
  KEY `comm_tenant_fk` (`tenant_id`),
  CONSTRAINT `comm_affiliate_fk` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comm_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comm_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
