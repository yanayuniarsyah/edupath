-- EduPath SaaS Unified Identity Migrations
-- Block 2: Unified Identity & RBAC Engine

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(36) NOT NULL,
  `identity_key` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identity_key` (`identity_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create user_roles table
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` VARCHAR(36) NOT NULL,
  `user_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NOT NULL,
  `role` VARCHAR(50) NOT NULL,
  `reference_id` VARCHAR(36) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `reference_id` (`reference_id`),
  UNIQUE KEY `user_tenant_role` (`user_id`, `tenant_id`, `role`),
  CONSTRAINT `ur_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ur_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Migrate Students to Users
INSERT IGNORE INTO `users` (`id`, `identity_key`, `password`, `is_active`)
SELECT UUID(), `email`, `password`, `is_active` FROM `students`;

INSERT IGNORE INTO `user_roles` (`id`, `user_id`, `tenant_id`, `role`, `reference_id`)
SELECT UUID(), u.`id`, s.`tenant_id`, 'student', s.`id`
FROM `students` s
JOIN `users` u ON u.`identity_key` = s.`email`;

-- 4. Migrate Admins to Users
INSERT IGNORE INTO `users` (`id`, `identity_key`, `password`, `is_active`)
SELECT UUID(), `username`, `password`, 1 FROM `admins`;

INSERT IGNORE INTO `user_roles` (`id`, `user_id`, `tenant_id`, `role`, `reference_id`)
SELECT UUID(), u.`id`, a.`tenant_id`, 'admin', a.`id`
FROM `admins` a
JOIN `users` u ON u.`identity_key` = a.`username`;

COMMIT;
