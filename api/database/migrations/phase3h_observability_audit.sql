-- EduPath SaaS Observability Migrations
-- Block 11: Audit Trail & Operational Reliability

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Create audit_logs table (Append-Only)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` VARCHAR(36) NOT NULL,
  `actor_id` VARCHAR(36) DEFAULT NULL, -- UUID of user/admin, NULL for unauthenticated actions (e.g. failed login)
  `tenant_id` VARCHAR(36) DEFAULT NULL, -- NULL for system-wide events
  `action` VARCHAR(100) NOT NULL,
  `target_id` VARCHAR(36) DEFAULT NULL,
  `metadata` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `al_actor_idx` (`actor_id`),
  KEY `al_tenant_idx` (`tenant_id`),
  KEY `al_action_idx` (`action`),
  -- Safe isolation relations
  CONSTRAINT `al_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
