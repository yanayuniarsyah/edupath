<?php
// api/database/migrations/phase13_partner_management.php
// Dual Partner Operating Models (PRD v1.1 Phase 4: AC-08, AC-09, AC-10)

require_once __DIR__ . '/../../config.php';

try {
    $pdo = get_db_connection();
    echo "Running Phase 13 Migration: Dual Partner Operating Models...\n";

    // 1. partner_profiles table
    $pdo->exec("
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
    ");
    echo "  [OK] Table partner_profiles verified.\n";

    // 2. partner_assignments table
    $pdo->exec("
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
    ");
    echo "  [OK] Table partner_assignments verified.\n";

    // 3. partner_assignment_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `partner_assignment_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `assignment_id` VARCHAR(36) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `actor_id` VARCHAR(36) NOT NULL,
            `metadata` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `pal_assign_idx` (`assignment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table partner_assignment_logs verified.\n";

    echo "Phase 13 Migration Completed Successfully.\n";
} catch (\Throwable $e) {
    echo "Phase 13 Migration Notice/Error: " . $e->getMessage() . "\n";
}
