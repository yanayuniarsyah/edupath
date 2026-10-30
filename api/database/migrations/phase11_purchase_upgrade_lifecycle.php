<?php
// api/database/migrations/phase11_purchase_upgrade_lifecycle.php
// Migration runner for Phase 11 — Purchase Snapshot & Full-Price Upgrade

require_once dirname(__DIR__, 2) . '/config.php';

function run_phase11_migration(PDO $pdo): array {
    $results = [];

    // 1. Extend orders table
    try {
        $orderCols = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);

        $toAdd = [
            'order_type'            => "ALTER TABLE orders ADD COLUMN order_type ENUM('new_purchase', 'upgrade', 'renewal') NOT NULL DEFAULT 'new_purchase' AFTER is_renewal",
            'package_version'       => "ALTER TABLE orders ADD COLUMN package_version INT NOT NULL DEFAULT 1 AFTER billing_cycle_snapshot",
            'purchase_snapshot'     => "ALTER TABLE orders ADD COLUMN purchase_snapshot JSON NULL COMMENT 'Snapshot komponen paket saat order' AFTER package_version",
            'upgrade_from_order_id' => "ALTER TABLE orders ADD COLUMN upgrade_from_order_id VARCHAR(36) NULL AFTER purchase_snapshot"
        ];

        foreach ($toAdd as $col => $sql) {
            if (!in_array($col, $orderCols)) {
                $pdo->exec($sql);
                $results[] = "Added column '$col' to 'orders'.";
            }
        }
    } catch (\Throwable $e) {
        $results[] = "Error extending orders: " . $e->getMessage();
    }

    // 2. Create upgrade_audit_logs table
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS upgrade_audit_logs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NULL,
                from_order_id VARCHAR(36) NULL,
                from_plan_id VARCHAR(36) NULL,
                to_order_id VARCHAR(36) NOT NULL,
                to_plan_id VARCHAR(36) NOT NULL,
                from_plan_name VARCHAR(150) NULL,
                to_plan_name VARCHAR(150) NOT NULL,
                upgrade_price_paid DECIMAL(15,2) NOT NULL COMMENT 'Harga penuh paket tujuan tanpa prorata (AC-04, AC-05)',
                previous_expires_at DATETIME NULL COMMENT 'Masa aktif paket lama (tidak dibatalkan, AC-06)',
                new_expires_at DATETIME NULL COMMENT 'Masa aktif paket baru dengan periode sendiri (AC-06)',
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY ual_student_fk (student_id),
                KEY ual_to_order_fk (to_order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $results[] = "Table 'upgrade_audit_logs' verified/created.";
    } catch (\Throwable $e) {
        $results[] = "Error on upgrade_audit_logs: " . $e->getMessage();
    }

    return $results;
}

if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $res = run_phase11_migration($pdo);
    if (php_sapi_name() === 'cli') {
        echo implode("\n", $res) . "\nMigration Phase 11 Done.\n";
    } else {
        echo json_encode(["success" => true, "results" => $res]);
    }
}
