-- ==============================================================================
-- PHASE 13 MIGRATION: DUAL PARTNER OPERATING MODELS (PRD v1.1 Phase 4)
-- AC-08: Partner tenant hanya mengakses tenant sendiri.
-- AC-09: Partner operator hanya mengakses assignment yang aktif.
-- AC-10: Revocation menghentikan akses tanpa menghapus histori.
-- ==============================================================================

-- 1. Create partner_profiles table
CREATE TABLE IF NOT EXISTS `partner_profiles` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `partner_type` ENUM('tenant', 'scoped_operator') NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `tenant_id` VARCHAR(36) NULL,
  `user_id` VARCHAR(36) NULL,
  `contact_email` VARCHAR(150) NULL,
  `contact_phone` VARCHAR(50) NULL,
  `status` ENUM('active', 'suspended', 'terminated') NOT NULL DEFAULT 'active',
  `revenue_share_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `pp_type_idx` (`partner_type`),
  KEY `pp_tenant_idx` (`tenant_id`),
  KEY `pp_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create partner_assignments table
CREATE TABLE IF NOT EXISTS `partner_assignments` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `partner_id` VARCHAR(36) NOT NULL,
  `operator_user_id` VARCHAR(36) NULL,
  `tenant_id` VARCHAR(36) NULL,
  `location` VARCHAR(255) NULL,
  `offering_id` VARCHAR(36) NULL,
  `class_id` VARCHAR(36) NULL,
  `session_id` VARCHAR(36) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `status` ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
  `revoked_at` DATETIME NULL,
  `revoked_by` VARCHAR(36) NULL,
  `revocation_reason` TEXT NULL,
  `created_by` VARCHAR(36) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `pa_partner_idx` (`partner_id`),
  KEY `pa_operator_idx` (`operator_user_id`),
  KEY `pa_tenant_idx` (`tenant_id`),
  KEY `pa_offering_idx` (`offering_id`),
  KEY `pa_class_idx` (`class_id`),
  KEY `pa_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create partner_assignment_logs table for audit trail
CREATE TABLE IF NOT EXISTS `partner_assignment_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` VARCHAR(36) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `actor_id` VARCHAR(36) NOT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `pal_assign_idx` (`assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
