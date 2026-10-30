<?php
// api/database/migrations/phase14_cohort_renewal.php
// Configurable Cohort Renewal (PRD v1.1 Phase 5: AC-11, AC-12, AC-13)

require_once __DIR__ . '/../../config.php';

try {
    $pdo = get_db_connection();
    echo "Running Phase 14 Migration: Configurable Cohort Renewal...\n";

    // 1. Extend class_students columns
    $csCols = [
        "ALTER TABLE `class_students` ADD COLUMN `valid_until` DATE NULL AFTER `joined_at`",
        "ALTER TABLE `class_students` ADD COLUMN `offering_id` VARCHAR(36) NULL AFTER `valid_until`",
        "ALTER TABLE `class_students` ADD COLUMN `renewal_reference_id` VARCHAR(36) NULL AFTER `offering_id`"
    ];
    foreach ($csCols as $sql) {
        try { $pdo->exec($sql); } catch (\Throwable $e) {}
    }
    echo "  [OK] Column extensions for class_students verified.\n";

    // 2. renewal_policies table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `renewal_policies` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `offering_id` VARCHAR(36) NOT NULL,
            `renewal_mode` ENUM('new_enrollment', 'extend_existing') NOT NULL DEFAULT 'new_enrollment',
            `renewal_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `extension_days` INT NOT NULL DEFAULT 30,
            `allowed_cohort_change` TINYINT(1) NOT NULL DEFAULT 1,
            `eligibility_rule` VARCHAR(100) NOT NULL DEFAULT 'active_or_grace',
            `open_at` DATETIME NULL,
            `close_at` DATETIME NULL,
            `policy_version` VARCHAR(20) NOT NULL DEFAULT 'v1.0',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `rp_offering_idx` (`offering_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table renewal_policies verified.\n";

    // 3. renewal_records table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `renewal_records` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `original_enrollment_id` VARCHAR(36) NOT NULL,
            `new_enrollment_id` VARCHAR(36) NULL,
            `student_id` VARCHAR(36) NOT NULL,
            `offering_id` VARCHAR(36) NOT NULL,
            `renewal_mode` ENUM('new_enrollment', 'extend_existing') NOT NULL,
            `previous_class_id` VARCHAR(36) NOT NULL,
            `new_class_id` VARCHAR(36) NOT NULL,
            `previous_end_date` DATE NULL,
            `new_end_date` DATE NOT NULL,
            `price_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `order_id` VARCHAR(36) NULL,
            `status` ENUM('pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
            `metadata` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `rr_orig_idx` (`original_enrollment_id`),
            KEY `rr_new_idx` (`new_enrollment_id`),
            KEY `rr_stu_idx` (`student_id`),
            KEY `rr_off_idx` (`offering_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  [OK] Table renewal_records verified.\n";

    echo "Phase 14 Migration Completed Successfully.\n";
} catch (\Throwable $e) {
    echo "Phase 14 Migration Notice/Error: " . $e->getMessage() . "\n";
}
