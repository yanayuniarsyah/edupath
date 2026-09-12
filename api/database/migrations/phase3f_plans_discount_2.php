<?php
require_once __DIR__ . '/../../config.php';
try {
    $pdo->exec("ALTER TABLE plans ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");
    $pdo->exec("ALTER TABLE plans ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) NOT NULL DEFAULT 0");
    echo "Success\n";
} catch (Exception $e) { echo $e->getMessage(); }

