<?php
require 'api/config.php';

try {
    $pdo->exec("
        ALTER TABLE questions 
        ADD COLUMN is_qc_passed TINYINT(1) DEFAULT 0
    ");
    echo "Column is_qc_passed added successfully to questions table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column is_qc_passed already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
