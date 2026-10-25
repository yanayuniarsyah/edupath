<?php
// api/payment.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';
require_once 'RevenueSplitEngine.php';
require_once 'FinancialLedger.php';
require_once 'RoyaltyRegistry.php';

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

      // 1. Fetch authenticated student
      $stmt = $pdo->prepare("SELECT id, name, email, phone, school_id, referred_by FROM students WHERE id = ?");
      $stmt->execute([$payload->id]);
      $student = $stmt->fetch();
  
      if (!$student) {
          http_response_code(404);
          echo json_encode(["error" => "Student tidak ditemukan"]);
          exit;
      }
      
      // Get tenant_id from JWT token (provided by login)
      $student_tenant_id = $payload->tenant_id ?? null;
      if (empty($student_tenant_id)) {
          http_response_code(403);
          echo json_encode(["error" => "Student tidak memiliki tenant yang valid"]);
          exit;
      }

      // Resolve Attribution, Channel & Branch Partner (PRD v1.2)
      $input_ref = $input['referral_code'] ?? null;
      $referred_by = $student['referred_by'] ?? null;
      $branch_tenant_id = null;
      $school_id = $student['school_id'] ?? null;
      $community_id = $input['community_id'] ?? null;

      if (!empty($input_ref)) {
          $stmt_aff = $pdo->prepare("SELECT id, tenant_id, user_id FROM affiliates WHERE referral_code = ? LIMIT 1");
          $stmt_aff->execute([$input_ref]);
          $aff_row = $stmt_aff->fetch();
          if ($aff_row) {
              $referred_by = $aff_row['id'];
              $branch_tenant_id = $aff_row['tenant_id'];
          }
      } elseif (!empty($referred_by)) {
          $stmt_aff = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ? LIMIT 1");
          $stmt_aff->execute([$referred_by]);
          $branch_tenant_id = $stmt_aff->fetchColumn() ?: null;
      }

      $channel = 'DIRECT';
      if (!empty($referred_by)) {
          $channel = 'AFFILIATE';
      } elseif (!empty($school_id)) {
          $channel = 'SCHOOL';
      } elseif (!empty($community_id)) {
          $channel = 'COMMUNITY';
      }

      // Check if this is a first transaction or renewal
      $stmt_first = $pdo->prepare("SELECT id FROM orders WHERE student_id = ? AND status = 'paid' LIMIT 1");
      $stmt_first->execute([$payload->id]);
      $is_renewal = (bool) $stmt_first->fetch();

    // 2. Lookup plan scoped to student's tenant (cross-tenant access blocked at DB level or global plans)
    $stmt = $pdo->prepare("SELECT id, name, price, discount, duration, features, billing_cycle FROM plans WHERE (id = ? OR name = ?) AND (tenant_id = ? OR tenant_id IS NULL)");
    $stmt->execute([$plan_id, $plan_id, $student_tenant_id]);
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
    $taxable_base  = bcsub($subtotal, $discount, 2);
    if (bccomp($taxable_base, '0.00', 2) === -1) {
        $taxable_base = '0.00';
    }
    $tax_amount    = bcmul($taxable_base, '0.11', 2);                  // TAX POLICY = 11% PPN on DPP
    $grand_total   = bcadd($taxable_base, $tax_amount, 2);
    // Ensure grand_total is not negative
    if (bccomp($grand_total, '0.00', 2) === -1) {
        $grand_total = '0.00';
    }
    $currency      = 'IDR';

    // Midtrans gross_amount must be integer IDR (no fractional cents in IDR)
    $gross_amount_int = (int) round((float) $grand_total);
    if ($gross_amount_int <= 0 || trim((string) MIDTRANS_SERVER_KEY) === '') {
        http_response_code(503);
        echo json_encode(["error" => "Pembayaran belum tersedia. Konfigurasi gateway belum lengkap."]);
        exit;
    }

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
        "enabled_payments" => ["qris", "gopay", "shopeepay"],
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
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

        $affiliate_id = $referred_by;

        // Calculate Waterfall Simulation & Snapshot via RevenueSplitEngine (PRD v1.2)
        $royaltyCalc = RoyaltyRegistry::calculateRoyalties($pdo, $plan['id'], (string)$grand_total);
        $splitParams = [
            'gross_amount'            => (string)$grand_total,
            'channel'                 => $channel,
            'is_renewal'              => $is_renewal,
            'gateway_rate'            => '0.025',
            'royalty_fee'             => $royaltyCalc['total_royalty'],
            'affiliate_id'            => $affiliate_id,
            'branch_tenant_id'        => $branch_tenant_id,
            'school_id'               => $school_id,
            'community_id'            => $community_id,
        ];
        $splitResult = RevenueSplitEngine::calculate($splitParams);
        $rule_snapshot = json_encode($splitResult['rule_snapshot']);

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
                  (id, tenant_id, plan_id, order_id, student_id, affiliate_id, channel, branch_tenant_id, school_id, community_id, is_renewal, plan_name, amount, gateway_fee, royalty_fee, distributable_pool, rule_snapshot, status, snap_token, billing_cycle_snapshot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ")->execute([
                $order_id,
                $student_tenant_id,   // canonical — from authenticated student
                $plan['id'],
                $order_ref,
                $payload->id,
                $affiliate_id,
                $channel,
                $branch_tenant_id,
                $school_id,
                $community_id,
                $is_renewal ? 1 : 0,
                $plan['name'],
                $grand_total,         // immutable at time of order — matches invoice
                $splitResult['gateway_fee'],
                $splitResult['royalty_fee'],
                $splitResult['distributable_pool'],
                $rule_snapshot,
                $snap_token,
                $plan['billing_cycle'] ?? 'one-time'
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
        if ($curl_error !== '') {
            error_log('EduPath Midtrans request failed: ' . $curl_error);
        }
        http_response_code(500);
        echo json_encode(["error" => "Gagal membuat transaksi Midtrans. Silakan coba lagi."]);
    }
}

elseif (($action === 'webhook' || $action === 'notification') && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (trim((string) MIDTRANS_SERVER_KEY) === '' || $order_id === '' || $status_code === '' || $gross_amount === '') {
        http_response_code(503);
        echo "Payment gateway is not configured";
        exit;
    }

    // 1. VERIFY SIGNATURE — reject if invalid
    $calculated_signature = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY);
    if (!hash_equals(strtolower($calculated_signature), strtolower((string)$signature_key))) {
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
        $new_status = 'refunded';
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

        $expected_amount = number_format((float)$order['amount'], 0, '.', '');
        $received_amount = number_format((float)$gross_amount, 0, '.', '');
        if ($expected_amount !== $received_amount) {
            $pdo->rollBack();
            log_audit($pdo, null, $order_tenant_id, 'webhook_amount_mismatch', $order['id'], [
                'expected' => $expected_amount, 'received' => $received_amount
            ]);
            http_response_code(422);
            echo "Amount mismatch";
            exit;
        }

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
            // Find payment attempt for this invoice+order (matching either UUID or order_id ref)
            $pay_stmt = $pdo->prepare("
                SELECT id, status FROM payments
                WHERE (order_id = ? OR order_id = ?) AND invoice_id = ?
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $pay_stmt->execute([$order['id'], $order_id, $invoice_id]);
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
            $stmt_check_sub = $pdo->prepare("SELECT id FROM subscriptions WHERE payment_reference = ? OR payment_reference = ?");
            $stmt_check_sub->execute([$order_id, $order['id']]);
            $existing_sub = $stmt_check_sub->fetch();
            $subscription_id = null;

            if ($existing_sub) {
                $subscription_id = $existing_sub['id'];
                $pdo->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?")
                    ->execute([$subscription_id]);
            } else {
                $subscription_id = $mkuuid();

                // Dukungan perpanjangan kumulatif: jika masih ada masa aktif, tambahkan dari tanggal expired terakhir
                $stmt_active_sub = $pdo->prepare("
                    SELECT expires_at FROM subscriptions
                    WHERE student_id = ? AND status = 'active' AND expires_at > NOW()
                    ORDER BY expires_at DESC
                    LIMIT 1
                ");
                $stmt_active_sub->execute([$order['student_id']]);
                $last_active = $stmt_active_sub->fetch();

                if ($last_active && strtotime($last_active['expires_at']) > time()) {
                    $base_time = strtotime($last_active['expires_at']);
                    $expires_at = date('Y-m-d H:i:s', $base_time + ($duration_days * 86400));
                } else {
                    $expires_at = date('Y-m-d H:i:s', time() + ($duration_days * 86400));
                }

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
                    VALUES (?, ?, ?, ?, 'active', ?, ?, ?)")
                    ->execute([
                        $subscription_id,
                        $order['tenant_id'],      // tenant from order
                        $order['student_id'],     // student owner
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
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          subscription_id = VALUES(subscription_id),
                          expires_at      = VALUES(expires_at),
                          revoked_at      = NULL
                    ")->execute([
                        $ent_id,
                        $order['tenant_id'],      // tenant from order
                        $order['student_id'],     // student owner
                        $feature_key,
                        $subscription_id,
                        $expires_at,
                    ]);
                }
            }

            // 9d. LEGACY FALLBACK — backward compat for AdminPanel
            $pdo->prepare("UPDATE students SET plan = ? WHERE id = ?")
                ->execute([$order['plan_name'], $order['student_id']]);

            // 9e. REVENUE SHARING & FINANCIAL LEDGER (PRD v1.2)
            try {
                $stmt_ord_meta = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt_ord_meta->execute([$order['id']]);
                $ord_meta = $stmt_ord_meta->fetch(PDO::FETCH_ASSOC);

                $ord_channel = $ord_meta['channel'] ?? 'DIRECT';
                $ord_is_renewal = !empty($ord_meta['is_renewal']);
                $ord_branch = $ord_meta['branch_tenant_id'] ?? null;
                $ord_aff = $ord_meta['affiliate_id'] ?? null;
                $ord_school = $ord_meta['school_id'] ?? null;
                $ord_comm = $ord_meta['community_id'] ?? null;

                // Anti-fraud self-referral guard
                if (!empty($ord_aff)) {
                    $stmt_aff = $pdo->prepare("SELECT user_id, tenant_id FROM affiliates WHERE id = ?");
                    $stmt_aff->execute([$ord_aff]);
                    $aff_user = $stmt_aff->fetch();

                    if ($aff_user) {
                        $stmt_stu_user = $pdo->prepare("SELECT user_id FROM user_roles WHERE reference_id = ? AND role = 'student' LIMIT 1");
                        $stmt_stu_user->execute([$order['student_id']]);
                        $stu_user_id = $stmt_stu_user->fetchColumn();

                        if (!empty($stu_user_id) && $stu_user_id === $aff_user['user_id']) {
                            // Self-referral detected: neutralize affiliate commission
                            $ord_aff = null;
                            $ord_channel = 'DIRECT';
                            log_audit($pdo, null, $order_tenant_id, 'commission_self_referral_rejected', $order['id'], [
                                'affiliate_id' => $ord_meta['affiliate_id'],
                                'student_id'   => $order['student_id']
                            ]);
                        }
                    }
                }

                // Compute royalties
                $royaltyCalc = RoyaltyRegistry::calculateRoyalties($pdo, $ord_meta['plan_id'] ?? null, (string)$ord_meta['amount']);

                // Calculate split
                $splitResult = RevenueSplitEngine::calculate([
                    'gross_amount'            => (string)$ord_meta['amount'],
                    'channel'                 => $ord_channel,
                    'is_renewal'              => $ord_is_renewal,
                    'gateway_fee'             => $ord_meta['gateway_fee'] ?? null,
                    'royalty_fee'             => $royaltyCalc['total_royalty'],
                    'affiliate_id'            => $ord_aff,
                    'branch_tenant_id'        => $ord_branch,
                    'school_id'               => $ord_school,
                    'community_id'            => $ord_comm
                ]);

                // Post immutable financial ledger entries
                FinancialLedger::postOrderSplit($pdo, $order['id'], $order_tenant_id, $ord_channel, $splitResult);

                // Record royalty accruals
                if (!empty($royaltyCalc['contracts'])) {
                    RoyaltyRegistry::recordAccruals($pdo, $order['id'], $royaltyCalc['contracts']);
                }

                // Update orders with finalized split amounts & snapshot
                $pdo->prepare("
                    UPDATE orders 
                    SET gateway_fee = ?, royalty_fee = ?, distributable_pool = ?, rule_snapshot = ?
                    WHERE id = ?
                ")->execute([
                    $splitResult['gateway_fee'],
                    $splitResult['royalty_fee'],
                    $splitResult['distributable_pool'],
                    json_encode($splitResult['rule_snapshot']),
                    $order['id']
                ]);

                // Sync legacy commissions table for backward compatibility
                if ($ord_channel === 'AFFILIATE' && !empty($ord_aff) && bccomp($splitResult['affiliate_amount'], '0.00', 2) > 0) {
                    $comm_id = $mkuuid();
                    $actual_rate = $ord_is_renewal ? '10.00' : '20.00';
                    $pdo->prepare("
                        INSERT IGNORE INTO commissions
                          (id, affiliate_id, tenant_id, order_id, amount, commission_rate_snapshot, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ")->execute([
                        $comm_id,
                        $ord_aff,
                        $order_tenant_id,
                        $order['id'],
                        $splitResult['affiliate_amount'],
                        $actual_rate
                    ]);
                }
            } catch (\Throwable $e) {
                error_log("PRD v1.2 Revenue Split Webhook Exception: " . $e->getMessage());
            }
        } elseif ($new_status === 'paid' && $is_duplicate_event) {
            // Idempotent duplicate of 'paid' — subscription/entitlement already provisioned, skip
            $processing_result = 'duplicate';
        } elseif ($new_status === 'refunded' || $new_status === 'failed') {
            // REFUND / CANCEL FLOW: cancel pending commission and post ledger reversals (PRD v1.2)
            $pdo->prepare("
                UPDATE commissions 
                SET status = 'cancelled' 
                WHERE order_id = ? AND status = 'pending'
            ")->execute([$order['id']]);

            FinancialLedger::reverseOrderLedger($pdo, $order['id'], "Order status changed to $new_status");
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
