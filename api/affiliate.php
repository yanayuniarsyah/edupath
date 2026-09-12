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
    
    echo json_encode([
        "status" => "joined",
        "referral_code" => $affiliate['referral_code'],
        "commission_rate" => $affiliate['commission_rate'],
        "stats" => [
            "total_referrals" => (int)$total_referrals,
            "total_paid" => (float)$total_paid,
            "total_pending" => (float)$total_pending
        ],
        "history" => $history
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Join affiliate program
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
    
    try {
        $stmt = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $user_id, $tenant_id, $referral_code, $commission_rate]);
        
        echo json_encode(["success" => true, "referral_code" => $referral_code]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}
