<?php
require_once __DIR__ . '/../../config.php';

try {
    $pdo->exec("ALTER TABLE plans ADD COLUMN IF NOT EXISTS discount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER price");
    $pdo->exec("ALTER TABLE plans ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");

    $pdo->exec("INSERT IGNORE INTO schema_migrations (migration_name, executed_at) VALUES ('phase3f_plans_discount', NOW())");

    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

