<?php
require 'api/config.php';

try {
    $pdo->exec("
        ALTER TABLE commissions 
        ADD COLUMN paid_by VARCHAR(36) NULL,
        ADD COLUMN paid_at DATETIME NULL,
        ADD COLUMN payout_reference VARCHAR(255) NULL
    ");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
