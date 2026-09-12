<?php
require_once __DIR__ . '/../../config.php';

echo "Running Phase 3 Questions Enhancements Migration...\n";

$queries = [
    "ALTER TABLE `questions` ADD COLUMN `classification` VARCHAR(20) DEFAULT 'LATIHAN' AFTER `subtes`;",
    "ALTER TABLE `question_imports_staging` ADD COLUMN `classification` VARCHAR(20) DEFAULT 'LATIHAN' AFTER `sub_materi`;"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Success: " . substr($sql, 0, 50) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Skipped (already exists): " . substr($sql, 0, 50) . "...\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration complete.\n";
