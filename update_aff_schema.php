<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';
require 'api/config.php';

try {
    $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_name VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_account VARCHAR(50) NULL");
    $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_owner VARCHAR(100) NULL");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
