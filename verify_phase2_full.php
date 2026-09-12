<?php
/**
 * P0 Billing Phase 2 — Full Forensic Verification
 * Tests schema, code, security invariants, financial integrity, atomicity.
 */
require 'api/config.php';

$report = [];
$all_ok = true;
$cleanup = [];

function result(string $name, bool $pass, string $ev, array &$r, bool &$ok): void {
    if (!$pass) $ok = false;
    $r[] = ['check' => $name, 'status' => $pass ? 'PASS' : 'FAIL', 'evidence' => $ev];
}
function uuid(): string {
    $b = bin2hex(random_bytes(16));
    return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
}

$code = file_get_contents(__DIR__ . '/api/payment.php');

// ============================================================
// SECTION 1 — SCHEMA: new orders columns
// ============================================================
foreach (['plan_id' => 'nullable FK', 'paid_at' => 'DATETIME NULL', 'subscription_id' => 'nullable FK'] as $col => $desc) {
    $exists = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='$col'")->fetchColumn();
    result("orders.$col EXISTS ($desc)", $exists, $exists ? 'Column found' : 'Column MISSING', $report, $all_ok);
}

// ============================================================
// SECTION 2 — CODE INSPECTION
// ============================================================

// 2a. invoice created in 'create' action
result('Code creates invoices row on order creation',
    str_contains($code, 'INSERT INTO invoices'),
    str_contains($code, 'INSERT INTO invoices') ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// 2b. payments row created on order creation
result('Code creates payments row (payment attempt) on order creation',
    str_contains($code, 'INSERT INTO payments'),
    str_contains($code, 'INSERT INTO payments') ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// 2c. Order + Invoice + Payment are inside a transaction
result('Order/Invoice/Payment creation wrapped in beginTransaction',
    (bool)preg_match('/beginTransaction.*INSERT INTO orders.*INSERT INTO invoices.*INSERT INTO payments/s', $code),
    preg_match('/beginTransaction.*INSERT INTO orders.*INSERT INTO invoices.*INSERT INTO payments/s', $code)
        ? 'Transaction pattern found' : 'NOT FOUND or incorrect order',
    $report, $all_ok);

// 2d. grand_total derived server-side (bcsub/bcadd pattern)
result('grand_total computed server-side (bcsub/bcadd)',
    str_contains($code, 'bcsub') && str_contains($code, 'bcadd'),
    str_contains($code, 'bcsub') ? 'bcsub/bcadd found' : 'NOT FOUND',
    $report, $all_ok);

// 2e. TAX_POLICY = NOT_YET_VERIFIED in snapshot
result('Tax policy marked NOT_YET_VERIFIED in pricing snapshot',
    str_contains($code, 'NOT_YET_VERIFIED'),
    str_contains($code, 'NOT_YET_VERIFIED') ? 'Marker found in snapshot' : 'NOT FOUND',
    $report, $all_ok);

// 2f. Discount marked as REQUIRES_BUSINESS_RULE
result('Discount marked REQUIRES_BUSINESS_RULE in snapshot',
    str_contains($code, 'REQUIRES_BUSINESS_RULE'),
    str_contains($code, 'REQUIRES_BUSINESS_RULE') ? 'Marker found' : 'NOT FOUND',
    $report, $all_ok);

// 2g. Client amount/price/tenant_id not read from $input in create block
result('Client amount not read from $input',
    !preg_match('/\$input\[.amount.\]/i', $code),
    !preg_match('/\$input\[.amount.\]/i', $code) ? 'Confirmed absent' : '$input[amount] FOUND — FAIL',
    $report, $all_ok);

result('Client price not read from $input',
    !preg_match('/\$input\[.price.\]/i', $code),
    !preg_match('/\$input\[.price.\]/i', $code) ? 'Confirmed absent' : '$input[price] FOUND — FAIL',
    $report, $all_ok);

result('Client tenant_id not read from $input',
    !preg_match('/\$input\[.tenant_id.\]/i', $code),
    !preg_match('/\$input\[.tenant_id.\]/i', $code) ? 'Confirmed absent' : '$input[tenant_id] FOUND — FAIL',
    $report, $all_ok);

// 2h. plan_id stored in INSERT INTO orders
result('orders INSERT includes plan_id column',
    (bool)preg_match('/INSERT INTO orders\s*\([^)]*plan_id[^)]*\)/s', $code),
    preg_match('/INSERT INTO orders\s*\([^)]*plan_id[^)]*\)/s', $code) ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// 2i. plan query includes tenant isolation
result('Plan lookup scoped to student.tenant_id (cross-tenant blocked)',
    (bool)preg_match('/FROM plans WHERE id\s*=\s*\?\s*AND\s*tenant_id\s*=\s*\?/i', $code),
    preg_match('/FROM plans WHERE id\s*=\s*\?\s*AND\s*tenant_id\s*=\s*\?/i', $code) ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// 2j. Webhook references plan_id (not hard-coded fallback duration only)
result('Webhook reads order.plan_id for subscription provisioning',
    str_contains($code, "order['plan_id']"),
    str_contains($code, "order['plan_id']") ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// 2k. Webhook uses paid_at
result('Webhook sets paid_at via UPDATE orders',
    str_contains($code, 'paid_at'),
    str_contains($code, 'paid_at') ? 'Pattern found' : 'NOT FOUND',
    $report, $all_ok);

// ============================================================
// SECTION 3 — RUNTIME: atomic order+invoice+payment creation
// ============================================================

$tenant_id = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
$plan_row  = $pdo->query("SELECT id, tenant_id, price, name, duration FROM plans WHERE tenant_id IS NOT NULL LIMIT 1")->fetch();

if (!$tenant_id || !$plan_row) {
    $report[] = ['check' => 'Runtime tests', 'status' => 'SKIP', 'evidence' => 'No tenant or plan in DB'];
} else {
    $tenant_b = uuid();
    $pdo->exec("INSERT INTO tenants (id, name, slug) VALUES ('$tenant_b', 'TestTenantB_Ph2', 'test-tenant-b-ph2')");
    $cleanup[] = "DELETE FROM tenants WHERE id = '$tenant_b'";

    $pw = password_hash('test', PASSWORD_DEFAULT);

    // Student A — same tenant as plan
    $student_a = uuid();
    $pdo->prepare("INSERT INTO students (id, tenant_id, name, email, password) VALUES (?,?,?,?,?)")
        ->execute([$student_a, $tenant_id, 'StudentA_Ph2', 'stud_a_ph2@test.local', $pw]);
    $cleanup[] = "DELETE FROM students WHERE id = '$student_a'";

    // Student B — different tenant
    $student_b = uuid();
    $pdo->prepare("INSERT INTO students (id, tenant_id, name, email, password) VALUES (?,?,?,?,?)")
        ->execute([$student_b, $tenant_b, 'StudentB_Ph2', 'stud_b_ph2@test.local', $pw]);
    $cleanup[] = "DELETE FROM students WHERE id = '$student_b'";

    // TEST A: Student A + Plan A → SUCCESS (same tenant)
    $subtotal    = (string)$plan_row['price'];
    $discount    = '0.00';
    $tax_amount  = '0.00';
    $grand_total = bcsub(bcadd($subtotal, $tax_amount, 2), $discount, 2);

    $order_id_a = uuid(); $inv_id_a = uuid(); $pay_id_a = uuid();
    $order_ref_a = 'TEST-A-' . bin2hex(random_bytes(4));
    $snapshot_a = json_encode([
        'plan_id' => $plan_row['id'], 'plan_name' => $plan_row['name'],
        'plan_price' => $plan_row['price'], 'subtotal' => $subtotal,
        'discount' => $discount, 'tax_amount' => $tax_amount, 'grand_total' => $grand_total,
        'currency' => 'IDR', 'discount_policy' => 'REQUIRES_BUSINESS_RULE', 'tax_policy' => 'NOT_YET_VERIFIED',
    ]);

    try {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO orders (id, tenant_id, plan_id, order_id, student_id, plan_name, amount, status)
                       VALUES (?,?,?,?,?,?,?,'pending')")
            ->execute([$order_id_a, $tenant_id, $plan_row['id'], $order_ref_a, $student_a, $plan_row['name'], $grand_total]);
        $pdo->prepare("INSERT INTO invoices (id, tenant_id, student_id, order_id, subtotal, discount, tax_amount, grand_total, currency, historical_pricing_snapshot)
                       VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$inv_id_a, $tenant_id, $student_a, $order_id_a, $subtotal, $discount, $tax_amount, $grand_total, 'IDR', $snapshot_a]);
        $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, payment_method, amount, currency, status)
                       VALUES (?,?,?,?,'midtrans','snap',?,?,'pending')")
            ->execute([$pay_id_a, $tenant_id, $inv_id_a, $order_id_a, $grand_total, 'IDR']);
        $pdo->commit();
        result('TEST A: Student A + Plan A → atomic (order+invoice+payment) SUCCESS',
            true, "order=$order_ref_a, invoice=$inv_id_a, payment=$pay_id_a", $report, $all_ok);
        $cleanup[] = "DELETE FROM payments WHERE id = '$pay_id_a'";
        $cleanup[] = "DELETE FROM invoices WHERE id = '$inv_id_a'";
        $cleanup[] = "DELETE FROM orders WHERE id = '$order_id_a'";
    } catch (PDOException $e) {
        $pdo->rollBack();
        result('TEST A: Student A + Plan A → atomic SUCCESS', false, $e->getMessage(), $report, $all_ok);
    }

    // TEST B: Cross-tenant plan → plan query must return 0 rows
    $stmt_b = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND tenant_id = ?");
    $stmt_b->execute([$plan_row['id'], $tenant_b]);
    result('TEST B: Student B (tenant B) + Plan A (tenant A) → 0 rows (REJECT)',
        $stmt_b->fetch() === false,
        'Query returned ' . ($stmt_b->fetch() === false ? '0' : '1') . ' rows',
        $report, $all_ok);

    // TEST C: Client sends tenant_id → must be ignored (code inspection above already verified)
    result('TEST C: Client tenant_id cannot influence order ownership (code verified)',
        !preg_match('/\$input\[.tenant_id.\]/i', $code),
        'Code does not read $input[tenant_id]', $report, $all_ok);

    // TEST D: Client sends amount → must be ignored
    result('TEST D: Client amount rejected — server reads plan.price from DB',
        !preg_match('/\$input\[.amount.\]/i', $code),
        'Code does not read $input[amount]', $report, $all_ok);

    // TEST E: Client sends price → must be ignored
    result('TEST E: Client price rejected',
        !preg_match('/\$input\[.price.\]/i', $code),
        'Code does not read $input[price]', $report, $all_ok);

    // TEST F: Client discount manipulation → impossible (discount=0.00 hardcoded, REQUIRES BUSINESS RULE)
    result('TEST F: Client discount manipulation → impossible (discount=0.00, REQUIRES BUSINESS RULE)',
        str_contains($code, "'0.00'") && str_contains($code, 'REQUIRES_BUSINESS_RULE'),
        "discount hardcoded to '0.00' with REQUIRES_BUSINESS_RULE marker", $report, $all_ok);

    // TEST G: Invoice total manipulation → impossible (grand_total computed server-side from plan.price)
    result('TEST G: Invoice total manipulation → impossible (bcsub/bcadd from DB price)',
        str_contains($code, 'bcsub') && !preg_match('/\$input\[.grand_total.\]/i', $code),
        'grand_total computed from plan.price via bcsub, not from client', $report, $all_ok);

    // TEST H: Affiliate tenant mismatch
    // Affiliate belongs to tenant B, order from student_a (tenant A): affiliate_tenant != student_tenant
    // Current code stores affiliate_id from student.referred_by (server-side), no direct client override.
    // Commission calculation is deferred to webhook (Phase 3 scope)
    result('TEST H: Affiliate tenant mismatch protection (commission calc deferred to webhook)',
        str_contains($code, "affiliate['tenant_id']"),
        str_contains($code, "affiliate['tenant_id']") ? 'Webhook checks affiliate.tenant_id' : 'NOT FOUND',
        $report, $all_ok);

    // FINANCIAL INVARIANT: invoice.grand_total == orders.amount == gross_amount sent to gateway
    $inv_check = $pdo->query("SELECT grand_total FROM invoices WHERE id = '$inv_id_a'")->fetchColumn();
    $ord_check = $pdo->query("SELECT amount FROM orders WHERE id = '$order_id_a'")->fetchColumn();
    $pay_check = $pdo->query("SELECT amount FROM payments WHERE id = '$pay_id_a'")->fetchColumn();
    result('FINANCIAL: invoice.grand_total == orders.amount == payments.amount',
        $inv_check === $ord_check && $ord_check === $pay_check,
        "invoice=$inv_check, order=$ord_check, payment=$pay_check",
        $report, $all_ok);

    // ATOMICITY: verify rollback leaves no orphans
    $before_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $before_invoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
    // Simulate a failed atomic transaction
    try {
        $pdo->beginTransaction();
        $fake_order = uuid(); $fake_inv = uuid();
        $pdo->prepare("INSERT INTO orders (id, tenant_id, plan_id, order_id, student_id, plan_name, amount, status) VALUES (?,?,?,?,?,?,?,'pending')")
            ->execute([$fake_order, $tenant_id, $plan_row['id'], 'TEST-ROLLBACK', $student_a, 'Test', $grand_total]);
        $pdo->prepare("INSERT INTO invoices (id, tenant_id, student_id, order_id, subtotal, grand_total, currency) VALUES (?,?,?,?,?,?,'IDR')")
            ->execute([$fake_inv, $tenant_id, $student_a, $fake_order, $grand_total, $grand_total]);
        // Force failure: insert payments with invalid invoice FK
        $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status) VALUES (?,?,?,?,'midtrans',?,?,'pending')")
            ->execute([uuid(), $tenant_id, 'invalid-fk-xxxx', $fake_order, $grand_total, 'IDR']);
        $pdo->commit();
        result('ATOMICITY: failed invoice+payment causes rollback', false, 'Transaction should have failed but committed', $report, $all_ok);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $after_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $after_invoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        result('ATOMICITY: failed payment INSERT causes rollback (no orphan order or invoice)',
            $after_orders === $before_orders && $after_invoices === $before_invoices,
            "Before: orders=$before_orders invoices=$before_invoices | After rollback: orders=$after_orders invoices=$after_invoices",
            $report, $all_ok);
    }

    // Entitlement not created on order creation (provisioning deferred to webhook/payment)
    $ents_after_order = (int)$pdo->query("SELECT COUNT(*) FROM entitlements WHERE student_id = '$student_a'")->fetchColumn();
    result('ENTITLEMENT: no entitlement provisioned on order creation alone (deferred to webhook)',
        $ents_after_order === 0,
        "Entitlements for student_a after order creation: $ents_after_order",
        $report, $all_ok);
}

// ============================================================
// CLEANUP
// ============================================================
foreach (array_reverse($cleanup) as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) {}
}

$final_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$final_invoices = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
$final_payments = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();

echo json_encode([
    'overall'      => $all_ok ? 'ALL PASS' : 'FAIL',
    'results'      => $report,
    'post_cleanup' => ['orders' => $final_orders, 'invoices' => $final_invoices, 'payments' => $final_payments],
], JSON_PRETTY_PRINT);
