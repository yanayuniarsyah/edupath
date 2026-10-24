<?php
require_once __DIR__ . '/../../config.php';

try {
    // 1. Alter plans
    try { $pdo->exec("ALTER TABLE `plans` ADD COLUMN `tenant_id` VARCHAR(36) NULL AFTER `id`"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE `plans` MODIFY COLUMN `price` DECIMAL(15,2) NOT NULL"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE `plans` ADD CONSTRAINT `plans_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE"); } catch (\Throwable $e) {}

    // 2. Alter orders
    try { $pdo->exec("ALTER TABLE `orders` ADD COLUMN `tenant_id` VARCHAR(36) NULL AFTER `id`"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `amount` DECIMAL(15,2) NOT NULL"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE `orders` ADD CONSTRAINT `orders_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE"); } catch (\Throwable $e) {}

    // Fix existing data (Set tenant_id for plans and orders if they exist)
    $first_tenant = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
    if ($first_tenant) {
        $pdo->exec("UPDATE `plans` SET `tenant_id` = '$first_tenant' WHERE `tenant_id` IS NULL");
    }

    // 3. Create invoices
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `invoices` (
            `id` VARCHAR(36) NOT NULL,
            `tenant_id` VARCHAR(36) NOT NULL,
            `student_id` VARCHAR(36) NOT NULL,
            `order_id` VARCHAR(36) NOT NULL,
            `subtotal` DECIMAL(15,2) NOT NULL,
            `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `grand_total` DECIMAL(15,2) NOT NULL,
            `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
            `historical_pricing_snapshot` JSON DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `inv_order_uq` (`order_id`),
            KEY `inv_tenant_fk` (`tenant_id`),
            KEY `inv_student_fk` (`student_id`),
            CONSTRAINT `inv_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `inv_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
            CONSTRAINT `inv_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Create payments
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` VARCHAR(36) NOT NULL,
            `tenant_id` VARCHAR(36) NOT NULL,
            `invoice_id` VARCHAR(36) NOT NULL,
            `order_id` VARCHAR(36) NOT NULL,
            `gateway` VARCHAR(50) NOT NULL DEFAULT 'midtrans',
            `payment_method` VARCHAR(50) DEFAULT 'qris',
            `gateway_transaction_id` VARCHAR(100) DEFAULT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `pay_tenant_fk` (`tenant_id`),
            KEY `pay_invoice_fk` (`invoice_id`),
            KEY `pay_order_fk` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Create payment_events_history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `payment_events_history` (
            `id` VARCHAR(36) NOT NULL,
            `payment_reference` VARCHAR(100) NOT NULL,
            `gateway_transaction_id` VARCHAR(100) DEFAULT NULL,
            `event_status` VARCHAR(50) NOT NULL,
            `processing_result` VARCHAR(50) NOT NULL,
            `raw_payload` JSON NOT NULL,
            `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo json_encode(["success" => true, "message" => "Phase 1 Billing Migration executed successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
