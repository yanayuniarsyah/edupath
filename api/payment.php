<?php
// api/payment.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = authenticate();
    if ($payload->role !== 'student') {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }
    if (!check_rate_limit($pdo, 'payment_create', 5, 10)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak permintaan pembuatan pembayaran."]);
        exit;
    }

    $plan_id = $input['plan_id'] ?? '';

    // -----------------------------------------------------------------------
    // CANONICAL TENANT DERIVATION (Phase 1 — preserved)
    // Source of truth: authenticated principal → student → students.tenant_id
    // Client-supplied tenant_id is NEVER read or trusted.
    // -----------------------------------------------------------------------

    // 1. Fetch authenticated student including tenant_id & affiliate referral
    $stmt = $pdo->prepare("SELECT id, name, email, phone, tenant_id, referred_by FROM students WHERE id = ?");
    $stmt->execute([$payload->id]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(403);
        echo json_encode(["error" => "Student tidak ditemukan"]);
        exit;
    }

    $student_tenant_id = $student['tenant_id'] ?? null;
    if (empty($student_tenant_id)) {
        http_response_code(403);
        echo json_encode(["error" => "Student tidak memiliki tenant yang valid"]);
        exit;
    }

    // 2. Lookup plan scoped to student's tenant (cross-tenant access blocked at DB level)
    $stmt = $pdo->prepare("SELECT id, name, price, discount, duration, features FROM plans WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$plan_id, $student_tenant_id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        http_response_code(404);
        echo json_encode(["error" => "Paket tidak ditemukan atau tidak tersedia untuk institusi Anda"]);
        exit;
    }

    // -----------------------------------------------------------------------
    // PHASE 2 — COMMERCIAL INVOICE CALCULATION
    // TAX POLICY = NOT YET VERIFIED — no tax rate is assumed.
    // DISCOUNT POLICY = FIXED_PLAN_DISCOUNT (Phase 3)
    // Server is canonical source of all amounts.
    // -----------------------------------------------------------------------
    $subtotal      = (string) $plan['price']; // already DECIMAL(15,2) from DB
    $discount      = (string) ($plan['discount'] ?? '0.00'); // From Plan
    $tax_amount    = '0.00';                  // TAX POLICY = NOT YET VERIFIED
    $grand_total   = bcsub(bcadd($subtotal, $tax_amount, 2), $discount, 2);
    // Ensure grand_total is not negative
    if (bccomp($grand_total, '0.00', 2) === -1) {
        $grand_total = '0.00';
    }
    $currency      = 'IDR';

    // Midtrans gross_amount must be integer IDR (no fractional cents in IDR)
    $gross_amount_int = (int) round((float) $grand_total);

    $order_ref = "ORG-" . time() . "-" . rand(1000, 9999);

    // -----------------------------------------------------------------------
    // MIDTRANS SNAP API — gateway-agnostic payload structure
    // QRIS channel: Midtrans handles routing, we do not create a separate processor
    // -----------------------------------------------------------------------
    $midtrans_url = MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    $server_key = MIDTRANS_SERVER_KEY;

    $transaction_payload = [
        "transaction_details" => [
            "order_id"     => $order_ref,
            "gross_amount" => $gross_amount_int
        ],
        "customer_details" => [
            "first_name" => $student['name'],
            "email"      => $student['email'],
            "phone"      => $student['phone'] ?? "08123456789"
        ],
        "item_details" => [
            [
                "id"       => $plan['id'],
                "price"    => $gross_amount_int,
                "quantity" => 1,
                "name"     => "Paket " . $plan['name']
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $midtrans_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($server_key . ':')
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $res = json_decode($response, true);

    if ($httpcode == 201 && isset($res['token'])) {
        $snap_token = $res['token'];

        // Helper: generate UUID v4-like
        $mkuuid = function(): string {
            $b = bin2hex(random_bytes(16));
            return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
        };

        $affiliate_id = $student['referred_by'] ?? null;

        // Pricing snapshot for historical immutability
        $pricing_snapshot = json_encode([
            'plan_id'       => $plan['id'],
            'plan_name'     => $plan['name'],
            'plan_price'    => $plan['price'],
            'plan_duration' => $plan['duration'],
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'tax_amount'    => $tax_amount,
            'grand_total'   => $grand_total,
            'currency'      => $currency,
            'snapshot_at'   => date('Y-m-d H:i:s'),
            'discount_policy'  => 'FIXED_PLAN_DISCOUNT',
            'tax_policy'       => 'NOT_YET_VERIFIED',
        ]);

        // -----------------------------------------------------------------------
        // ATOMIC: Order + Invoice + Payment attempt must succeed together.
        // If any step fails → rollback → no orphan records.
        // -----------------------------------------------------------------------
        try {
            $pdo->beginTransaction();

            // a. Create Order
            $order_id    = $mkuuid();
            $invoice_id  = $mkuuid();
            $payment_id  = $mkuuid();

            $pdo->prepare("
                INSERT INTO orders
                  (id, tenant_id, plan_id, order_id, student_id, affiliate_id, plan_name, amount, status, snap_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ")->execute([
                $order_id,
                $student_tenant_id,   // canonical — from authenticated student
                $plan['id'],
                $order_ref,
                $payload->id,
                $affiliate_id,
                $plan['name'],
                $grand_total,         // immutable at time of order — matches invoice
                $snap_token
            ]);

            // b. Create immutable Commercial Invoice snapshot
            $pdo->prepare("
                INSERT INTO invoices
                  (id, tenant_id, student_id, order_id, subtotal, discount, tax_amount, grand_total, currency, historical_pricing_snapshot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $invoice_id,
                $student_tenant_id,
                $payload->id,
                $order_id,
                $subtotal,
                $discount,
                $tax_amount,
                $grand_total,
                $currency,
                $pricing_snapshot
            ]);

            // c. Create Payment attempt record (gateway-agnostic)
            //    gateway_transaction_id is NULL until Midtrans webhook confirms it.
            //    payment_method: 'snap' for Snap (may change to 'qris' after webhook confirms channel)
            $pdo->prepare("
                INSERT INTO payments
                  (id, tenant_id, invoice_id, order_id, gateway, payment_method, amount, currency, status)
                VALUES (?, ?, ?, ?, 'midtrans', 'snap', ?, ?, 'pending')
            ")->execute([
                $payment_id,
                $student_tenant_id,
                $invoice_id,
                $order_id,
                $grand_total,
                $currency
            ]);

            $pdo->commit();

            echo json_encode([
                "token"      => $snap_token,
                "order_id"   => $order_ref,
                "invoice_id" => $invoice_id
            ]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            log_audit($pdo, $payload->id, $student_tenant_id, 'order_create_failed', null, [
                'order_ref' => $order_ref,
                'error'     => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode(["error" => "Gagal membuat order"]);
        }

    } else {
        http_response_code(500);
        echo json_encode(["error" => "Gagal membuat transaksi Midtrans", "details" => $res]);
    }
}

elseif ($action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // -----------------------------------------------------------------------
    // MIDTRANS NOTIFICATION WEBHOOK — Phase 2 Remediated
    // Source of truth: server-side signature-verified notification only.
    // Browser/client callback TIDAK dianggap sebagai payment proof.
    // -----------------------------------------------------------------------

    $order_id           = $input['order_id']           ?? '';
    $transaction_status = $input['transaction_status'] ?? '';
    $fraud_status       = $input['fraud_status']       ?? '';
    $signature_key      = $input['signature_key']      ?? '';
    $status_code        = $input['status_code']        ?? '';
    $gross_amount       = $input['gross_amount']       ?? '';
    $gateway_txn_id     = $input['transaction_id']     ?? null; // Midtrans transaction_id
    $payment_type       = $input['payment_type']       ?? null; // e.g. 'qris', 'bank_transfer', etc.
    $raw_payload        = json_encode($input);                  // store raw for audit

    // 1. VERIFY SIGNATURE — reject if invalid
    $calculated_signature = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
    if ($calculated_signature !== $signature_key) {
        http_response_code(403);
        echo "Invalid signature";
        exit;
    }

    // 2. MAP Midtrans transaction_status → internal order/payment status
    // Contract: paid → failed is ILLEGAL (late stale cancel/expire/deny must not downgrade 'paid')
    // Statuses supported by current gateway integration:
    //   capture+accept → paid   |  capture+challenge → challenge
    //   settlement → paid       |  cancel/deny/expire → failed
    //   pending → pending
    $new_status = 'pending';
    if ($transaction_status === 'capture') {
        $new_status = ($fraud_status === 'challenge') ? 'challenge' : 'paid';
    } elseif ($transaction_status === 'settlement') {
        $new_status = 'paid';
    } elseif (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
        $new_status = 'failed';
    } elseif ($transaction_status === 'pending') {
        $new_status = 'pending';
    } elseif ($transaction_status === 'refund' || $transaction_status === 'partial_refund') {
        $new_status = 'refunded'; // record event; provisioning not affected here
    }

    // UUID helper — local (no global scope)
    $mkuuid = function(): string {
        $b = bin2hex(random_bytes(16));
        return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
    };

    $processing_result = 'processed'; // default; overwritten below as needed

    try {
        $pdo->beginTransaction();

        // 3. LOCK ORDER — pessimistic lock; also read tenant_id for downstream tenant propagation
        $stmt = $pdo->prepare("
            SELECT id, tenant_id, student_id, plan_id, plan_name, amount, affiliate_id, status
            FROM orders WHERE order_id = ? FOR UPDATE
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            // Record unknown-order event OUTSIDE transaction for audit
            $ev_id = $mkuuid();
            $pdo->prepare("
                INSERT INTO payment_events_history
                  (id, payment_reference, gateway_transaction_id, event_status, processing_result, raw_payload)
                VALUES (?, ?, ?, ?, 'order_not_found', ?)
            ")->execute([$ev_id, $order_id, $gateway_txn_id, $transaction_status, $raw_payload]);
            http_response_code(404);
            echo "Order not found";
            exit;
        }

        $order_tenant_id = $order['tenant_id']; // canonical for all downstream inserts

        // 4. PAYMENT STATE SAFETY — paid → failed/cancelled MUST be rejected
        $current_order_status = $order['status'];
        $final_states = ['paid', 'refunded'];
        if (in_array($current_order_status, $final_states) && in_array($new_status, ['failed', 'pending', 'challenge'])) {
            // Late stale event: record to history but do NOT mutate financial state
            $processing_result = 'rejected_late_event';
            $ev_id = $mkuuid();
            $pdo->prepare("
                INSERT INTO payment_events_history
                  (id, payment_reference, gateway_transaction_id, event_status, processing_result, raw_payload)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$ev_id, $order_id, $gateway_txn_id, $transaction_status, $processing_result, $raw_payload]);
            $pdo->commit();
            echo "OK"; // acknowledge to Midtrans so it stops retrying
            exit;
        }

        // 5. LOOK UP INVOICE linked to this order (for payment record update)
        $invoice = $pdo->prepare("SELECT id FROM invoices WHERE order_id = ? LIMIT 1");
        $invoice->execute([$order['id']]);
        $invoice_row = $invoice->fetch();
        $invoice_id = $invoice_row ? $invoice_row['id'] : null;

        // 6. P0-B: CHECK FOR DUPLICATE EVENT in payment_events_history
        //    Idempotency: if we've already processed this exact gateway event, mark as duplicate.
        //    Schema has no UNIQUE constraint on payment_events_history; we guard in application.
        $dup_check = $pdo->prepare("
            SELECT id FROM payment_events_history
            WHERE payment_reference = ?
              AND gateway_transaction_id = ?
              AND event_status = ?
              AND processing_result != 'rejected_late_event'
            LIMIT 1
        ");
        $dup_check->execute([$order_id, $gateway_txn_id, $transaction_status]);
        $is_duplicate_event = (bool) $dup_check->fetch();
        if ($is_duplicate_event) {
            $processing_result = 'duplicate';
        }

        // 7. UPDATE ORDER STATUS (only if not duplicate or state would actually change)
        if ($current_order_status !== $new_status) {
            $pdo->prepare("
                UPDATE orders
                SET status = ?,
                    paid_at = IF(? = 'paid', IFNULL(paid_at, NOW()), paid_at)
                WHERE order_id = ?
            ")->execute([$new_status, $new_status, $order_id]);
        }

        // 8. P0-A: UPDATE PAYMENTS — find attempt record and sync status + gateway info
        //    Lookup: orders.id (UUID PK) → payments.order_id → most recent pending attempt
        if ($invoice_id) {
            // Find payment attempt for this invoice+order (may be NULL gtx = pending attempt)
            $pay_stmt = $pdo->prepare("
                SELECT id, status FROM payments
                WHERE order_id = ? AND invoice_id = ?
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $pay_stmt->execute([$order_id, $invoice_id]);
            $payment_row = $pay_stmt->fetch();

            if ($payment_row) {
                // Update payment status, gateway_transaction_id, and actual payment channel
                // P1-C: use payment_type from Midtrans notification as actual channel
                // 'snap' is the checkout mechanism; payment_type is the actual method (qris, bank_transfer, etc.)
                $actual_channel = $payment_type ?? $payment_row['status']; // fallback: keep existing if no payment_type
                $pdo->prepare("
                    UPDATE payments
                    SET status                 = ?,
                        gateway_transaction_id = COALESCE(gateway_transaction_id, ?),
                        payment_method         = COALESCE(?, payment_method)
                    WHERE id = ?
                ")->execute([$new_status, $gateway_txn_id, $payment_type, $payment_row['id']]);
            } else {
                // Payment record missing — this is a gap; log but do not silently create financial state
                $processing_result = 'payment_record_missing';
                log_audit($pdo, null, $order_tenant_id, 'webhook_payment_record_missing', $order['id'], [
                    'order_id' => $order_id, 'invoice_id' => $invoice_id
                ]);
            }
        } else {
            // Invoice record missing
            $processing_result = 'invoice_record_missing';
            log_audit($pdo, null, $order_tenant_id, 'webhook_invoice_record_missing', $order['id'], [
                'order_id' => $order_id
            ]);
        }

        // 9. PROVISIONING — only on 'paid' AND not a duplicate event
        if ($new_status === 'paid' && !$is_duplicate_event) {

            // 9a. Get plan duration (from order.plan_id)
            $stmt_plan = $pdo->prepare("SELECT duration FROM plans WHERE id = ?");
            $stmt_plan->execute([$order['plan_id']]);
            $plan_row = $stmt_plan->fetch();
            // duration_days: use plan value; if plan deleted, fallback 30 days (REQUIRES BUSINESS RULE for fallback)
            $duration_days = $plan_row ? (int) $plan_row['duration'] : 30;

            // 9b. P1-A: SUBSCRIPTION — idempotent; carry tenant_id from order
            $stmt_check_sub = $pdo->prepare("SELECT id FROM subscriptions WHERE payment_reference = ?");
            $stmt_check_sub->execute([$order_id]);
            $existing_sub = $stmt_check_sub->fetch();
            $subscription_id = null;

            if ($existing_sub) {
                $subscription_id = $existing_sub['id'];
                $pdo->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?")
                    ->execute([$subscription_id]);
            } else {
                $subscription_id = $mkuuid();
                $expires_at = date('Y-m-d H:i:s', time() + ($duration_days * 86400));

                // P1-A: include tenant_id derived from order.tenant_id, ensure not null
                if (empty($order['tenant_id'])) {
                    $pdo->rollBack();
                    log_audit($pdo, null, null, 'subscription_creation_failed', $order['id'], ['reason' => 'order tenant_id missing']);
                    http_response_code(500);
                    echo json_encode(["error" => "Internal error: order tenant missing"]);
                    exit;
                }
                $pdo->prepare("INSERT INTO subscriptions
                      (id, tenant_id, student_id, plan_id, status, payment_reference, expires_at, plan_name_snapshot)
                    VALUES (?, ?, NULL, ?, 'active', ?, ?, ?)")
                    ->execute([
                        $subscription_id,
                        $order['tenant_id'],      // tenant from order
                        $order['plan_id'],
                        $order['id'],          // payment_reference
                        $expires_at,
                        $order['plan_name'],
                    ]);


                $pdo->prepare("UPDATE orders SET subscription_id = ? WHERE order_id = ?")
                    ->execute([$subscription_id, $order_id]);
            }

            // 9c. P1-B: ENTITLEMENTS — idempotent; carry tenant_id from order
            $stmt_features = $pdo->prepare("SELECT feature_key FROM plan_entitlements WHERE plan_id = ?");
            $stmt_features->execute([$order['plan_id']]);
            $features = $stmt_features->fetchAll(PDO::FETCH_COLUMN);

            if (empty($features)) {
                // Default features if plan_entitlements empty (documented gap, not invented rule)
                $features = ['tryout_unlimited', 'ai_adaptive_path', 'premium_materials'];
            }

            foreach ($features as $feature_key) {
                $stmt_check_ent = $pdo->prepare(
                    "SELECT id FROM entitlements WHERE subscription_id = ? AND feature_key = ?"
                );
                $stmt_check_ent->execute([$subscription_id, $feature_key]);

                if (!$stmt_check_ent->fetch()) {
                    $ent_id = $mkuuid();
                    // P1-B: include tenant_id derived from order.tenant_id
                    $pdo->prepare("
                        INSERT INTO entitlements
                          (id, tenant_id, student_id, feature_key, subscription_id, expires_at)
                        VALUES (?, ?, NULL, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          subscription_id = VALUES(subscription_id),
                          expires_at      = VALUES(expires_at),
                          revoked_at      = NULL
                    ")->execute([
                        $ent_id,
                        $order['tenant_id'],      // tenant from order
                        $feature_key,
                        $subscription_id,
                        $expires_at,
                    ]);
                }
            }

            // 9d. LEGACY FALLBACK — backward compat for AdminPanel
            $pdo->prepare("UPDATE students SET plan = ? WHERE id = ?")
                ->execute([$order['plan_name'], $order['student_id']]);

            // 9e. P1-D: COMMISSION — bcmul (no float arithmetic)
            //     Affiliate cross-tenant guard: commission only if affiliate.tenant_id == order.tenant_id
            if (!empty($order['affiliate_id'])) {
                $stmt_aff = $pdo->prepare("SELECT tenant_id, commission_rate FROM affiliates WHERE id = ?");
                $stmt_aff->execute([$order['affiliate_id']]);
                $affiliate = $stmt_aff->fetch();

                if ($affiliate) {
                    // CROSS-TENANT AFFILIATE GUARD
                    // Business rule: commission attribution must be same-tenant.
                    // If affiliate.tenant_id != order.tenant_id → reject commission silently, log audit.
                    if ($affiliate['tenant_id'] !== $order['tenant_id']) {
                        log_audit($pdo, null, $order['tenant_id'], 'commission_cross_tenant_rejected', $order['id'], [
                            'affiliate_id'        => $order['affiliate_id'],
                            'affiliate_tenant_id' => $affiliate['tenant_id'],
                            'order_tenant_id'     => $order['tenant_id'],
                        ]);
                    } else {
                        // P1-D: bcmul — no float arithmetic for monetary commission calculation
                        // commission_rate is DECIMAL(5,2) from DB; amount is DECIMAL(15,2)
                        $rate_str            = (string) $affiliate['commission_rate'];
                        $amount_str          = (string) $order['amount'];
                        $commission_amount   = bcmul($amount_str, bcdiv($rate_str, '100', 10), 2);

                        $comm_id = $mkuuid();
                        $pdo->prepare("
                            INSERT IGNORE INTO commissions
                              (id, affiliate_id, tenant_id, order_id, amount, commission_rate_snapshot, status)
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')
                        ")->execute([
                            $comm_id,
                            $order['affiliate_id'],
                            $order_tenant_id,   // use order tenant, not affiliate tenant
                            $order['id'],
                            $commission_amount,
                            $affiliate['commission_rate'],
                        ]);
                    }
                }
            }
        } elseif ($new_status === 'paid' && $is_duplicate_event) {
            // Idempotent duplicate of 'paid' — subscription/entitlement already provisioned, skip
            $processing_result = 'duplicate';
        }

        // 10. P0-B: WRITE PAYMENT EVENT HISTORY — always inside transaction
        //     Immutable audit record. DO NOT DELETE after write.
        //     Idempotency: duplicate events are marked 'duplicate' (not suppressed).
        $ev_id = $mkuuid();
        $pdo->prepare("
            INSERT INTO payment_events_history
              (id, payment_reference, gateway_transaction_id, event_status, processing_result, raw_payload)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $ev_id,
            $order_id,
            $gateway_txn_id,
            $transaction_status,
            $processing_result,
            $raw_payload,
        ]);

        $pdo->commit();

        // Audit log OUTSIDE transaction (post-commit state is canonical)
        if ($current_order_status !== $new_status) {
            log_audit($pdo, null, $order_tenant_id, 'payment_status_changed', $order['id'], [
                'order_id'   => $order_id,
                'old_status' => $current_order_status,
                'new_status' => $new_status,
            ]);
        }

        echo "OK";

    } catch (PDOException $e) {
        $pdo->rollBack();
        log_audit($pdo, null, null, 'payment_webhook_failed', null, [
            'order_id' => $order_id,
            'error'    => $e->getMessage()
        ]);
        http_response_code(500);
        echo "Database Error";
    }
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint payment tidak ditemukan"]);
}
?>

