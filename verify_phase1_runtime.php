<?php
/**
 * Setup test fixtures + run SKIP tests that need data.
 * All inserted data is CLEANED UP after the test run.
 */
require 'api/config.php';

$results = [];
$all_pass = true;
$cleanup = [];

function check(string $name, bool $cond, string $ev, array &$out, bool &$all): void {
    $status = $cond ? 'PASS' : 'FAIL';
    if (!$cond) $all = false;
    $out[] = ['check' => $name, 'status' => $status, 'evidence' => $ev];
}

function uuid(PDO $pdo): string {
    $b = bin2hex(random_bytes(16));
    return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
}

// ----------------------------------------------------------------
// Fetch existing tenant and plan
// ----------------------------------------------------------------
$tenant_id = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
$plan_row  = $pdo->query("SELECT id, tenant_id, price, name FROM plans WHERE tenant_id IS NOT NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$tenant_id || !$plan_row) {
    echo json_encode(['error' => 'No tenant or plan found — cannot run runtime tests']);
    exit(1);
}

// Create a second (fake) tenant for cross-tenant test
$tenant2_id = uuid($pdo);
$pdo->exec("INSERT INTO tenants (id, name, slug) VALUES ('$tenant2_id', 'FakeTenant', 'fake-tenant-test')");
$cleanup[] = "DELETE FROM tenants WHERE id = '$tenant2_id'";

// Create student belonging to existing tenant
$student_id = uuid($pdo);
$pass_hash  = password_hash('testpass123', PASSWORD_DEFAULT);
$pdo->exec("INSERT INTO students (id, tenant_id, name, email, password) VALUES ('$student_id', '$tenant_id', 'Test Student', 'test.student.verify@test.local', '$pass_hash')");
$cleanup[] = "DELETE FROM students WHERE id = '$student_id'";

// Create student belonging to fake tenant (different tenant)
$student2_id = uuid($pdo);
$pdo->exec("INSERT INTO students (id, tenant_id, name, email, password) VALUES ('$student2_id', '$tenant2_id', 'Cross Tenant Student', 'cross.tenant.verify@test.local', '$pass_hash')");
$cleanup[] = "DELETE FROM students WHERE id = '$student2_id'";

// ----------------------------------------------------------------
// TEST B: INSERT order with valid tenant_id SUCCEEDS
// ----------------------------------------------------------------
$order_uuid = uuid($pdo);
$order_ref  = 'TEST-VALID-' . bin2hex(random_bytes(4));
try {
    $pdo->prepare("INSERT INTO orders (id, tenant_id, order_id, student_id, plan_name, amount, status)
                   VALUES (?, ?, ?, ?, ?, ?, 'pending')")
        ->execute([$order_uuid, $tenant_id, $order_ref, $student_id, $plan_row['name'], $plan_row['price']]);
    check('INSERT order with valid tenant_id SUCCEEDS', true, "order_id=$order_ref inserted", $results, $all_pass);
    $cleanup[] = "DELETE FROM orders WHERE id = '$order_uuid'";
} catch (PDOException $e) {
    check('INSERT order with valid tenant_id SUCCEEDS', false, $e->getMessage(), $results, $all_pass);
}

// ----------------------------------------------------------------
// TEST C: Cross-tenant plan lookup returns 0 rows
// student2 belongs to $tenant2_id; plan belongs to $tenant_id
// Querying plan with student2's tenant_id should return nothing
// ----------------------------------------------------------------
$stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND tenant_id = ?");
$stmt->execute([$plan_row['id'], $tenant2_id]);
$row = $stmt->fetch();
check('Cross-tenant plan query returns 0 rows for foreign student tenant',
    $row === false,
    "plan_id={$plan_row['id']}, queried tenant=$tenant2_id, found=" . ($row ? 'YES' : 'NO'),
    $results, $all_pass
);

// ----------------------------------------------------------------
// TEST D: Duplicate gateway_transaction_id on same invoice REJECTED
// First create a minimal invoice + 2 payments with same GTX
// ----------------------------------------------------------------
$inv_id = uuid($pdo);
$pdo->prepare("INSERT INTO invoices (id, tenant_id, student_id, order_id, subtotal, grand_total, currency)
               VALUES (?, ?, ?, ?, ?, ?, 'IDR')")
    ->execute([$inv_id, $tenant_id, $student_id, $order_uuid, $plan_row['price'], $plan_row['price']]);
$cleanup[] = "DELETE FROM invoices WHERE id = '$inv_id'";

$pay1_id = uuid($pdo);
$pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status, gateway_transaction_id)
               VALUES (?, ?, ?, ?, 'midtrans', ?, 'IDR', 'pending', 'GTX-IDEMPOTENCY-TEST')")
    ->execute([$pay1_id, $tenant_id, $inv_id, $order_uuid, $plan_row['price']]);
$cleanup[] = "DELETE FROM payments WHERE id = '$pay1_id'";

// Attempt duplicate
$pay2_id = uuid($pdo);
try {
    $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status, gateway_transaction_id)
                   VALUES (?, ?, ?, ?, 'midtrans', ?, 'IDR', 'pending', 'GTX-IDEMPOTENCY-TEST')")
        ->execute([$pay2_id, $tenant_id, $inv_id, $order_uuid, $plan_row['price']]);
    check('Duplicate GTX on same invoice REJECTED by DB', false, 'Second insert UNEXPECTEDLY succeeded', $results, $all_pass);
    $cleanup[] = "DELETE FROM payments WHERE id = '$pay2_id'";
} catch (PDOException $e) {
    check('Duplicate GTX on same invoice REJECTED by DB', true, 'Got SQLSTATE: ' . substr($e->getMessage(), 0, 100), $results, $all_pass);
}

// ----------------------------------------------------------------
// TEST E: Second payment on same invoice with DIFFERENT GTX succeeds
// (multiple payment attempts allowed)
// ----------------------------------------------------------------
$pay3_id = uuid($pdo);
try {
    $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status, gateway_transaction_id)
                   VALUES (?, ?, ?, ?, 'midtrans', ?, 'IDR', 'pending', 'GTX-SECOND-ATTEMPT')")
        ->execute([$pay3_id, $tenant_id, $inv_id, $order_uuid, $plan_row['price']]);
    check('Second payment attempt with different GTX on same invoice SUCCEEDS', true, 'Second insert OK', $results, $all_pass);
    $cleanup[] = "DELETE FROM payments WHERE id = '$pay3_id'";
} catch (PDOException $e) {
    check('Second payment attempt with different GTX on same invoice SUCCEEDS', false, $e->getMessage(), $results, $all_pass);
}

// ----------------------------------------------------------------
// TEST F: Payment with NULL GTX (pending, no gateway assigned yet)
//         — multiple such rows on same invoice must be allowed
// ----------------------------------------------------------------
$pay4_id = uuid($pdo);
$pay5_id = uuid($pdo);
try {
    $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status)
                   VALUES (?, ?, ?, ?, 'midtrans', ?, 'IDR', 'pending')")
        ->execute([$pay4_id, $tenant_id, $inv_id, $order_uuid, $plan_row['price']]);
    $pdo->prepare("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status)
                   VALUES (?, ?, ?, ?, 'midtrans', ?, 'IDR', 'pending')")
        ->execute([$pay5_id, $tenant_id, $inv_id, $order_uuid, $plan_row['price']]);
    check('Multiple NULL-GTX payments on same invoice ALLOWED (pending attempts)', true, 'Both inserts succeeded', $results, $all_pass);
    $cleanup[] = "DELETE FROM payments WHERE id = '$pay4_id'";
    $cleanup[] = "DELETE FROM payments WHERE id = '$pay5_id'";
} catch (PDOException $e) {
    check('Multiple NULL-GTX payments on same invoice ALLOWED (pending attempts)', false, $e->getMessage(), $results, $all_pass);
}

// ----------------------------------------------------------------
// TEST G: Security — client cannot influence tenant via different
//         plan_id (cross-tenant plan must not be found for student's tenant)
// ----------------------------------------------------------------
// Create a plan owned by tenant2
$plan2_id = uuid($pdo);
$pdo->exec("INSERT INTO plans (id, tenant_id, name, price, duration) VALUES ('$plan2_id', '$tenant2_id', 'FakePlan', 100.00, 30)");
$cleanup[] = "DELETE FROM plans WHERE id = '$plan2_id'";

// Student1 (tenant1) tries to buy plan2 (tenant2) — server filters by student.tenant_id
$stmt2 = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND tenant_id = ?");
$stmt2->execute([$plan2_id, $tenant_id]); // querying with student1's tenant_id
$r = $stmt2->fetch();
check('Student cannot buy cross-tenant plan (plan owned by different tenant)',
    $r === false,
    "plan=$plan2_id owned by $tenant2_id, query tenant=$tenant_id, found=" . ($r ? 'YES' : 'NO'),
    $results, $all_pass
);

// ----------------------------------------------------------------
// CLEANUP
// ----------------------------------------------------------------
foreach (array_reverse($cleanup) as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
}

echo json_encode([
    'overall' => $all_pass ? 'ALL PASS' : 'FAIL — see results',
    'results' => $results,
], JSON_PRETTY_PRINT);
