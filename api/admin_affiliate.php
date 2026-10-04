<?php
// api/admin_affiliate.php
require_once 'config.php';
require_once 'jwt.php';

$payload = authenticate();

if (!in_array($payload->role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Only admins can access this"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'payouts') {
        try {
            $stmt = $pdo->query("
                SELECT p.*, 
                    COALESCE(a.referral_code, '-') as referral_code,
                    COALESCE(a.bank_name, '-') as bank_name,
                    COALESCE(a.bank_account, '-') as bank_account,
                    COALESCE(a.bank_owner, '-') as bank_owner,
                    COALESCE(s.name, u.identity_key, 'Mitra Afiliasi') as affiliate_name 
                FROM payouts p 
                LEFT JOIN affiliates a ON p.affiliate_id = a.id
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                ORDER BY p.created_at DESC
            ");
            $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($payouts);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } elseif ($action === 'commissions') {
        try {
            $stmt = $pdo->query("
                SELECT 
                    c.id, c.affiliate_id, c.tenant_id, c.order_id, c.amount, c.commission_rate_snapshot, c.status, c.created_at,
                    COALESCE(o.plan_name, 'Paket Belajar') as plan_name,
                    COALESCE(s_order.name, 'Siswa') as student_name,
                    COALESCE(a.referral_code, '-') as referral_code,
                    COALESCE(s_aff.name, u_aff.identity_key, 'Mitra') as affiliate_name
                FROM commissions c
                LEFT JOIN orders o ON (c.order_id = o.id OR c.order_id = o.order_id)
                LEFT JOIN students s_order ON o.student_id = s_order.id
                LEFT JOIN affiliates a ON c.affiliate_id = a.id
                LEFT JOIN users u_aff ON a.user_id = u_aff.id
                LEFT JOIN user_roles ur_aff ON (u_aff.id = ur_aff.user_id AND ur_aff.role = 'student')
                LEFT JOIN students s_aff ON (ur_aff.reference_id = s_aff.id OR u_aff.identity_key = s_aff.email)
                ORDER BY c.created_at DESC
            ");
            $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($commissions);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } else {
        // list affiliates
        try {
            $stmt = $pdo->query("
                SELECT 
                    a.id, a.user_id, a.tenant_id, a.referral_code, a.commission_rate,
                    COALESCE(a.bank_name, '') as bank_name,
                    COALESCE(a.bank_account, '') as bank_account,
                    COALESCE(a.bank_owner, '') as bank_owner,
                    a.created_at,
                    COALESCE(u.identity_key, s.email, 'Mitra') as identity_key,
                    COALESCE(s.name, a.bank_owner, u.identity_key, 'Mitra Afiliasi') as affiliate_name,
                    COALESCE(s.email, u.identity_key, '-') as email,
                    (SELECT COUNT(*) FROM students WHERE referred_by = a.id) as total_referrals,
                    (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission
                FROM affiliates a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                ORDER BY a.created_at DESC
            ");
            $affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($affiliates);
        } catch (PDOException $e) {
            error_log("Affiliates query failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Gagal membaca data afiliasi", "detail" => $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
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
                http_response_code(404);
                echo json_encode(["error" => "Payout not found or already processed"]);
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
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    } elseif ($action === 'payout_commission') {
        $comm_id = $input['commission_id'] ?? '';
        $ref = $input['payout_reference'] ?? 'MANUAL_TRANSFER';
        try {
            $stmt = $pdo->prepare("UPDATE commissions SET status = 'paid' WHERE id = ?");
            $stmt->execute([$comm_id]);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update commission status"]);
        }
    }
}
