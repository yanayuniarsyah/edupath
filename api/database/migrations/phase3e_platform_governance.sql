-- EduPath SaaS Platform Governance Migrations
-- Block 7: Superadmin & Tenant Lifecycle

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Drop existing FK to modify column safely
ALTER TABLE `user_roles` DROP FOREIGN KEY `ur_tenant_fk`;

-- 2. Modify tenant_id to be NULLable for Superadmins
ALTER TABLE `user_roles` MODIFY `tenant_id` VARCHAR(36) NULL;

-- 3. Re-add FK with NULL allowance
ALTER TABLE `user_roles` 
ADD CONSTRAINT `ur_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- 4. Bootstrap Initial Superadmin
-- Note: In production, password should be injected securely via CLI/Environment.
-- This uses a placeholder password 'superadmin123' for demonstration of structure only.
-- User is expected to change this immediately.
INSERT IGNORE INTO `users` (`id`, `identity_key`, `password`, `is_active`) 
VALUES (
    'sa-0000-0000-0000-000000000000', 
    'superadmin@edupath.local', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password' (placeholder bcrypt)
    1
);

INSERT IGNORE INTO `user_roles` (`id`, `user_id`, `tenant_id`, `role`, `reference_id`) 
VALUES (
    'ur-sa-00-0000-0000-00000000000', 
    'sa-0000-0000-0000-000000000000', 
    NULL, 
    'superadmin', 
    'sa-0000-0000-0000-000000000000' -- superadmin has no separate profile table for now
);

COMMIT;
