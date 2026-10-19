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
        `notes` text DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    
    // Ensure notes column exists in case payouts table was created earlier without it
    try {
        $pdo->exec("ALTER TABLE `payouts` ADD COLUMN `notes` text DEFAULT NULL AFTER `bank_owner`");
    } catch (Exception $e) {
        // column may already exist
    }

    echo "Table 'payouts' verified and updated.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
