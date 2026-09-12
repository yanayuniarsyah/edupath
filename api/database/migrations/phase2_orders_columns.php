<?php
/**
 * P0 Billing Phase 2 Migration
 * Adds missing columns to `orders` required by webhook & invoice flow.
 *
 * Missing columns confirmed by forensic audit (phase2_audit.json):
 *   orders.plan_id        — MISSING (webhook reads it to get plan duration)
 *   orders.paid_at        — MISSING (webhook sets it on payment)
 *   orders.subscription_id — MISSING (webhook writes the linked subscription)
 *
 * No existing order data. Safe to add nullable columns.
 */

require_once __DIR__ . '/../../config.php';

// Pre-flight: confirm no orders exist that would be affected
$order_count = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

try {
    // orders.plan_id — FK to plans, nullable (historical orders may not have plan_id if plan deleted)
    $pdo->exec("ALTER TABLE `orders`
        ADD COLUMN `plan_id` VARCHAR(36) NULL AFTER `tenant_id`,
        ADD CONSTRAINT `orders_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
    ");

    // orders.paid_at — set by webhook when payment confirmed
    $pdo->exec("ALTER TABLE `orders`
        ADD COLUMN `paid_at` DATETIME NULL AFTER `status`
    ");

    // orders.subscription_id — written by webhook after subscription is provisioned
    $pdo->exec("ALTER TABLE `orders`
        ADD COLUMN `subscription_id` VARCHAR(36) NULL AFTER `paid_at`,
        ADD CONSTRAINT `orders_sub_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
    ");

    echo json_encode([
        "success" => true,
        "message" => "Phase 2 schema migration complete",
        "changes" => [
            "orders.plan_id"         => "ADDED VARCHAR(36) NULL FK→plans",
            "orders.paid_at"         => "ADDED DATETIME NULL",
            "orders.subscription_id" => "ADDED VARCHAR(36) NULL FK→subscriptions",
        ],
        "pre_flight" => ["existing_orders" => $order_count]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit(1);
}
