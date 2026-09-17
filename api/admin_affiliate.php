<?php
require_once 'config.php';
require_once 'jwt.php';

$payload = authenticate();

if ($payload->role !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Only admins can access this"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'payouts') {
        $stmt = $pdo->query("
            SELECT p.*, a.referral_code, s.name as affiliate_name 
            FROM payouts p 
            JOIN affiliates a ON p.affiliate_id = a.id
            JOIN students s ON a.user_id = s.user_id
            ORDER BY p.created_at DESC
        ");
        $payouts = $stmt->fetchAll();
        echo json_encode($payouts);
    } else {
        // list affiliates
        $stmt = $pdo->query("
            SELECT a.*, s.name as affiliate_name, s.email,
            (SELECT COUNT(*) FROM students WHERE referred_by = a.id) as total_referrals,
            (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission
            FROM affiliates a
            JOIN students s ON a.user_id = s.user_id
        ");
        $affiliates = $stmt->fetchAll();
        echo json_encode($affiliates);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'approve_payout') {
        $payout_id = $input['payout_id'] ?? '';
        
        $pdo->beginTransaction();
        try {
            // Get payout
            $stmt = $pdo->prepare("SELECT * FROM payouts WHERE id = ? AND status = 'pending'");
            $stmt->execute([$payout_id]);
            $payout = $stmt->fetch();
            
            if (!$payout) {
                http_response_code(404); echo json_encode(["error" => "Payout not found or already processed"]);
                $pdo->rollBack();
                exit;
            }
            
            // Mark payout as paid
            $stmt = $pdo->prepare("UPDATE payouts SET status = 'paid' WHERE id = ?");
            $stmt->execute([$payout_id]);
            
            // Mark requested commissions as paid
            $stmt = $pdo->prepare("UPDATE commissions SET status = 'paid' WHERE affiliate_id = ? AND status = 'requested'");
            $stmt->execute([$payout['affiliate_id']]);
            
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Database error"]);
        }
    }
}
