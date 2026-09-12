<?php
/**
 * P0 Billing Phase 1 — Blocker Remediation Migration
 *
 * Blocker 1: ALTER orders.tenant_id to NOT NULL
 * Blocker 3: Add UNIQUE strategy + indexes on payments & payment_events_history
 *
 * Pre-condition: orders table has 0 rows (verified by forensic report).
 * No DEFAULT tenant is used. No fallback is applied.
 */

require_once __DIR__ . '/../../config.php';

// -- 0. Pre-flight safety check ------------------------------------------
$order_count = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
if ($order_count > 0) {
    echo json_encode([
        "success" => false,
        "error"   => "ABORT: orders table is not empty ($order_count rows). Manual data migration required before schema change."
    ]);
    exit(1);
}

try {
    // DDL statements are auto-committed by MySQL even inside a transaction.
    // We run them individually and stop on the first failure.

    // -- BLOCKER 1: Make orders.tenant_id NOT NULL -----------------------
    $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `tenant_id` VARCHAR(36) NOT NULL");

    // -- BLOCKER 3a: UNIQUE composite on (invoice_id, gateway_transaction_id)
    // We do NOT put UNIQUE on gateway_transaction_id alone because:
    //   - the column is nullable (a NULL value today = no gateway assignment yet)
    //   - multiple payment attempts on the same invoice must be allowed
    //   - only ONE completed payment per invoice should map to a specific gateway tx
    // Strategy: partial uniqueness via composite (invoice_id, gateway_transaction_id)
    // MySQL UNIQUE composite NULLs: each (invoice_id, NULL) pair is treated as unique,
    // so multiple NULL gateway_transaction_id rows are allowed (pending attempts).
    // Once a gateway_transaction_id is assigned it must be unique per invoice.
    $pdo->exec("ALTER TABLE `payments` ADD UNIQUE KEY `pay_invoice_gtx_uq` (`invoice_id`, `gateway_transaction_id`)");

    // -- BLOCKER 3b: Additional index on gateway_transaction_id alone for webhook lookup
    $pdo->exec("ALTER TABLE `payments` ADD INDEX `pay_gtx_idx` (`gateway_transaction_id`)");

    // -- PAYMENT EVENTS HISTORY: indexes for audit lookup ----------------
    $pdo->exec("ALTER TABLE `payment_events_history` ADD INDEX `peh_payment_ref_idx` (`payment_reference`)");
    $pdo->exec("ALTER TABLE `payment_events_history` ADD INDEX `peh_gtx_idx` (`gateway_transaction_id`)");

    echo json_encode([
        "success"  => true,
        "message"  => "Blocker 1 + Blocker 3 schema remediation complete.",
        "changes"  => [
            "orders.tenant_id"                           => "MODIFIED → NOT NULL (no default)",
            "payments UNIQUE pay_invoice_gtx_uq"         => "ADDED (invoice_id, gateway_transaction_id)",
            "payments INDEX pay_gtx_idx"                 => "ADDED (gateway_transaction_id)",
            "payment_events_history INDEX peh_payment_ref_idx" => "ADDED",
            "payment_events_history INDEX peh_gtx_idx"  => "ADDED",
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit(1);
}
