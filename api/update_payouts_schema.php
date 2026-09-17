<?php
$_SERVER['REQUEST_METHOD'] = 'CLI';
require 'config.php';

try {
    // Create payouts table
    $sql = "CREATE TABLE IF NOT EXISTS `payouts` (
        `id` varchar(36) NOT NULL,
        `affiliate_id` varchar(36) NOT NULL,
        `amount` float NOT NULL,
        `status` varchar(20) DEFAULT 'pending',
        `bank_name` varchar(50) DEFAULT NULL,
        `bank_account` varchar(50) DEFAULT NULL,
        `bank_owner` varchar(100) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'payouts' created or already exists.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
