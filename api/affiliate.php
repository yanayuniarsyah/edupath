<?php
require_once 'config.php';
require_once 'jwt.php';

$payload = authenticate();

if ($payload->role !== 'student') {
    http_response_code(403);
    echo json_encode(["error" => "Only students can access affiliate dashboard"]);
    exit;
}

$user_id = $payload->user_id;
$tenant_id = $payload->tenant_id;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Affiliate profile
    $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE user_id = ? AND tenant_id = ?");
    $stmt->execute([$user_id, $tenant_id]);
    $affiliate = $stmt->fetch();
    
    if (!$affiliate) {
        echo json_encode(["status" => "not_joined"]);
        exit;
    }
    
    $affiliate_id = $affiliate['id'];
    
    // Get Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE referred_by = ?");
    $stmt->execute([$affiliate_id]);
    $total_referrals = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'paid'");
    $stmt->execute([$affiliate_id]);
    $total_paid = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'pending'");
    $stmt->execute([$affiliate_id]);
    $total_pending = $stmt->fetchColumn();
    
    // Get recent commissions
    $stmt = $pdo->prepare("SELECT amount, status, created_at FROM commissions WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$affiliate_id]);
    $history = $stmt->fetchAll();
    
    // Get referrals list
    $stmt = $pdo->prepare("SELECT name, school, plan, created_at FROM students WHERE referred_by = ? ORDER BY created_at DESC");
    $stmt->execute([$affiliate_id]);
    $referrals = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "joined",
        "referral_code" => $affiliate['referral_code'],
        "commission_rate" => $affiliate['commission_rate'],
        "bank_info" => [
            "bank_name" => $affiliate['bank_name'],
            "bank_account" => $affiliate['bank_account'],
            "bank_owner" => $affiliate['bank_owner']
        ],
        "stats" => [
            "total_referrals" => (int)$total_referrals,
            "total_paid" => (float)$total_paid,
            "total_pending" => (float)$total_pending
        ],
        "history" => $history,
        "referrals" => $referrals
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'update_bank') {
        $bank_name = $input['bank_name'] ?? '';
        $bank_account = $input['bank_account'] ?? '';
        $bank_owner = $input['bank_owner'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE affiliates SET bank_name = ?, bank_account = ?, bank_owner = ? WHERE user_id = ? AND tenant_id = ?");
        $stmt->execute([$bank_name, $bank_account, $bank_owner, $user_id, $tenant_id]);
        
        echo json_encode(["success" => true]);
        exit;
    }
    
    if ($action === 'request_payout') {
        $amount = (float)($input['amount'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id, bank_name, bank_account, bank_owner FROM affiliates WHERE user_id = ? AND tenant_id = ?");
        $stmt->execute([$user_id, $tenant_id]);
        $aff = $stmt->fetch();
        
        if (!$aff) {
            http_response_code(400); echo json_encode(["error" => "Affiliate not found"]); exit;
        }
        
        // Check pending balance
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'pending'");
        $stmt->execute([$aff['id']]);
        $pending = (float)$stmt->fetchColumn();
        
        if ($amount < 50000 || $amount > $pending) {
            http_response_code(400); echo json_encode(["error" => "Invalid amount or insufficient balance"]); exit;
        }
        
        $payout_id = bin2hex(random_bytes(16));
        $payout_id = substr($payout_id,0,8).'-'.substr($payout_id,8,4).'-'.substr($payout_id,12,4).'-'.substr($payout_id,16,4).'-'.substr($payout_id,20,12);
        
        $pdo->beginTransaction();
        try {
            // Create payout request
            $stmt = $pdo->prepare("INSERT INTO payouts (id, affiliate_id, amount, status, bank_name, bank_account, bank_owner) VALUES (?, ?, ?, 'pending', ?, ?, ?)");
            $stmt->execute([$payout_id, $aff['id'], $amount, $aff['bank_name'], $aff['bank_account'], $aff['bank_owner']]);
            
            // Mark commissions as requested (deduct pending)
            // Wait, if we mark them requested, they are no longer pending.
            $stmt = $pdo->prepare("UPDATE commissions SET status = 'requested' WHERE affiliate_id = ? AND status = 'pending'");
            $stmt->execute([$aff['id']]);
            
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Failed to request payout"]);
        }
        exit;
    }

    // Join affiliate program (default POST action)
    $stmt = $pdo->prepare("SELECT id FROM affiliates WHERE user_id = ? AND tenant_id = ?");
    $stmt->execute([$user_id, $tenant_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Already joined"]);
        exit;
    }
    
    // Get student name to create referral code
    $stmt = $pdo->prepare("SELECT name FROM students WHERE user_id = ? AND tenant_id = ?");
    $stmt->execute([$user_id, $tenant_id]);
    $student = $stmt->fetch();
    $name = $student ? $student['name'] : 'USER';
    
    // Clean name for code (alphanumeric uppercase)
    $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($name));
    $base_code = substr($clean_name, 0, 5);
    $random_suffix = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
    $referral_code = $base_code . $random_suffix;
    
    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
    
    $commission_rate = 20.00; // Default 20%
    
    // Bank info
    $bank_name = $input['bank_name'] ?? null;
    $bank_account = $input['bank_account'] ?? null;
    $bank_owner = $input['bank_owner'] ?? null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, bank_name, bank_account, bank_owner) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $user_id, $tenant_id, $referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner]);
        
        echo json_encode(["success" => true, "referral_code" => $referral_code]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}
