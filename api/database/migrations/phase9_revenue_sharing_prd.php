<?php
// api/database/migrations/phase9_revenue_sharing_prd.php
require_once __DIR__ . '/../../config.php';

echo "Running Phase 9: EduPath Revenue Sharing, Attribution, Royalty & Financial Ledger Migration...\n";

try {
    // 1. Alter orders table columns
    $orderCols = [
        'channel'            => "VARCHAR(30) DEFAULT 'DIRECT'",
        'branch_tenant_id'   => "VARCHAR(36) DEFAULT NULL",
        'school_id'          => "VARCHAR(36) DEFAULT NULL",
        'community_id'       => "VARCHAR(36) DEFAULT NULL",
        'is_renewal'         => "TINYINT(1) DEFAULT 0",
        'gateway_fee'        => "DECIMAL(15,2) DEFAULT '0.00'",
        'royalty_fee'        => "DECIMAL(15,2) DEFAULT '0.00'",
        'distributable_pool' => "DECIMAL(15,2) DEFAULT '0.00'",
        'rule_snapshot'      => "LONGTEXT DEFAULT NULL"
    ];

    foreach ($orderCols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `$col` $def");
            echo "✅ Column orders.$col added.\n";
        } catch (PDOException $e) {
            echo "ℹ️ Column orders.$col already present or error ignored.\n";
        }
    }

    // 2. financial_ledger table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `financial_ledger` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `order_id` VARCHAR(36) NOT NULL,
            `tenant_id` VARCHAR(36) DEFAULT NULL,
            `channel` VARCHAR(30) NOT NULL DEFAULT 'DIRECT',
            `entry_type` VARCHAR(50) NOT NULL,
            `party_type` VARCHAR(50) NOT NULL,
            `party_id` VARCHAR(100) DEFAULT NULL,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
            `is_credit` TINYINT(1) NOT NULL DEFAULT 1,
            `rule_snapshot` LONGTEXT DEFAULT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'posted',
            `reversal_ref_id` VARCHAR(36) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_fl_order` (`order_id`),
            INDEX `idx_fl_party` (`party_type`, `party_id`),
            INDEX `idx_fl_type` (`entry_type`),
            INDEX `idx_fl_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✅ Table financial_ledger ensured.\n";

    // 3. content_owners table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `content_owners` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `bank_name` VARCHAR(50) DEFAULT NULL,
            `bank_account` VARCHAR(50) DEFAULT NULL,
            `bank_owner` VARCHAR(150) DEFAULT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_co_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✅ Table content_owners ensured.\n";

    // 4. royalty_contracts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `royalty_contracts` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `owner_id` VARCHAR(36) NOT NULL,
            `title` VARCHAR(200) NOT NULL,
            `plan_id` VARCHAR(36) DEFAULT NULL,
            `package_key` VARCHAR(100) DEFAULT NULL,
            `percentage_rate` DECIMAL(5,2) NOT NULL DEFAULT '0.00',
            `per_use_rate` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
            `valid_from` DATE NOT NULL,
            `valid_to` DATE DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `terms_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_rc_owner` (`owner_id`),
            INDEX `idx_rc_plan` (`plan_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✅ Table royalty_contracts ensured.\n";

    // 5. royalty_accruals table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `royalty_accruals` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `contract_id` VARCHAR(36) NOT NULL,
            `owner_id` VARCHAR(36) NOT NULL,
            `order_id` VARCHAR(36) NOT NULL,
            `percentage_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
            `usage_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ra_order` (`order_id`),
            INDEX `idx_ra_owner` (`owner_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✅ Table royalty_accruals ensured.\n";

    // 6. communities table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `communities` (
            `id` VARCHAR(36) NOT NULL PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `school_id` VARCHAR(36) DEFAULT NULL,
            `contact_name` VARCHAR(150) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `revenue_share_rate` DECIMAL(5,2) NOT NULL DEFAULT '3.00',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✅ Table communities ensured.\n";

    echo "🎉 Phase 9 Migration Completed Successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
