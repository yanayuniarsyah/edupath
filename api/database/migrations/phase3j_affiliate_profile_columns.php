<?php
require_once __DIR__ . '/../../config.php';

try {
    $stmt = $pdo->query("DESCRIBE affiliates");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('name', $cols)) {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN name VARCHAR(150) NULL");
    }
    if (!in_array('email', $cols)) {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN email VARCHAR(150) NULL");
    }
    if (!in_array('bank_name', $cols)) {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_name VARCHAR(50) NULL");
    }
    if (!in_array('bank_account', $cols)) {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_account VARCHAR(50) NULL");
    }
    if (!in_array('bank_owner', $cols)) {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_owner VARCHAR(100) NULL");
    }

    try {
        $pdo->exec("INSERT IGNORE INTO schema_migrations (migration_name, executed_at) VALUES ('phase3j_affiliate_profile_columns', NOW())");
    } catch (\Throwable $t) {}

    echo "Migration phase3j_affiliate_profile_columns completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
