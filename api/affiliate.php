<?php
require_once 'config.php';
require_once 'jwt.php';

$payload = authenticate();

if (!in_array($payload->role, ['student', 'affiliate'])) {
    http_response_code(403);
    echo json_encode(["error" => "Hanya siswa atau mitra afiliasi yang dapat mengakses"]);
    exit;
}

$user_id = $payload->user_id;
$tenant_id = $payload->tenant_id;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Affiliate profile
    $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE user_id = ? OR id = ?");
    $stmt->execute([$user_id, $payload->id]);
    $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$affiliate) {
        if ($payload->role === 'affiliate') {
            $aff_id = bin2hex(random_bytes(16));
            $aff_id = substr($aff_id,0,8).'-'.substr($aff_id,8,4).'-'.substr($aff_id,12,4).'-'.substr($aff_id,16,4).'-'.substr($aff_id,20,12);
            $uStmt = $pdo->prepare("SELECT identity_key FROM users WHERE id = ?");
            $uStmt->execute([$user_id]);
            $uRow = $uStmt->fetch();
            $prefix = $uRow ? explode('@', $uRow['identity_key'])[0] : 'MITRA';
            $ref_code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $prefix), 0, 5)) . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
            $tId = $tenant_id ?: 'default_tenant';
            $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, created_at) VALUES (?, ?, ?, ?, 20.00, NOW())")
                ->execute([$aff_id, $user_id, $tId, $ref_code]);
            $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE id = ?");
            $stmt->execute([$aff_id]);
            $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo json_encode(["status" => "not_joined"]);
            exit;
        }
    }
    
    $affiliate_id = $affiliate['id'];
    
    // Auto-generate referral code if missing
    if (empty($affiliate['referral_code'])) {
        $uStmt = $pdo->prepare("SELECT identity_key FROM users WHERE id = ?");
        $uStmt->execute([$user_id]);
        $uRow = $uStmt->fetch();
        $name = !empty($affiliate['bank_owner']) ? $affiliate['bank_owner'] : ($uRow ? explode('@', $uRow['identity_key'])[0] : 'MITRA');
        
        $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($name));
        $base_code = substr($clean_name, 0, 5) ?: 'EDUP';
        $random_suffix = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
        $new_code = $base_code . $random_suffix;
        
        $pdo->prepare("UPDATE affiliates SET referral_code = ? WHERE id = ?")->execute([$new_code, $affiliate_id]);
        $affiliate['referral_code'] = $new_code;
    }
    
    // Get Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE referred_by = ?");
    $stmt->execute([$affiliate_id]);
    $total_referrals = $stmt->fetchColumn() ?: 0;
    
    // Active referrals: students with active subscriptions or paid plans
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) 
        FROM students s 
        LEFT JOIN subscriptions sub ON s.id = sub.student_id AND sub.status = 'active'
        WHERE s.referred_by = ? AND (sub.id IS NOT NULL OR (s.plan IS NOT NULL AND LOWER(s.plan) != 'free'))
    ");
    $stmt->execute([$affiliate_id]);
    $active_referrals = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'paid'");
    $stmt->execute([$affiliate_id]);
    $total_paid = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'pending'");
    $stmt->execute([$affiliate_id]);
    $total_pending = $stmt->fetchColumn();
    
    // Get recent commissions
    $stmt = $pdo->prepare("SELECT id, amount, status, created_at FROM commissions WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$affiliate_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get referrals list with plan & status
    $stmt = $pdo->prepare("
        SELECT s.name, s.email, s.school, s.target_ptn, COALESCE(s.plan, 'free') as plan, s.created_at,
               CASE WHEN sub.id IS NOT NULL OR (s.plan IS NOT NULL AND LOWER(s.plan) != 'free') THEN 'Aktif' ELSE 'Trial / Free' END as status
        FROM students s 
        LEFT JOIN subscriptions sub ON s.id = sub.student_id AND sub.status = 'active'
        WHERE s.referred_by = ? 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$affiliate_id]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payouts list
    $payouts = [];
    try {
        $stmt = $pdo->prepare("SELECT id, amount, status, bank_name, bank_account, bank_owner, created_at, notes FROM payouts WHERE affiliate_id = ? ORDER BY created_at DESC");
        $stmt->execute([$affiliate_id]);
        $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $payouts = [];
    }
    
    echo json_encode([
        "status" => "joined",
        "referral_code" => $affiliate['referral_code'],
        "commission_rate" => (float)$affiliate['commission_rate'],
        "bank_info" => [
            "bank_name" => $affiliate['bank_name'] ?: '',
            "bank_account" => $affiliate['bank_account'] ?: '',
            "bank_owner" => $affiliate['bank_owner'] ?: ''
        ],
        "stats" => [
            "total_referrals" => (int)$total_referrals,
            "active_referrals" => (int)$active_referrals,
            "total_paid" => (float)$total_paid,
            "total_pending" => (float)$total_pending
        ],
        "history" => $history,
        "referrals" => $referrals,
        "payouts" => $payouts
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }
    $action = $input['action'] ?? '';

    if ($action === 'update_bank') {
        $bank_name = $input['bank_name'] ?? '';
        $bank_account = $input['bank_account'] ?? '';
        $bank_owner = $input['bank_owner'] ?? '';
        
        if (!empty($tenant_id)) {
            $stmt = $pdo->prepare("UPDATE affiliates SET bank_name = ?, bank_account = ?, bank_owner = ? WHERE user_id = ? AND (tenant_id = ? OR tenant_id IS NULL)");
            $stmt->execute([$bank_name, $bank_account, $bank_owner, $user_id, $tenant_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE affiliates SET bank_name = ?, bank_account = ?, bank_owner = ? WHERE user_id = ?");
            $stmt->execute([$bank_name, $bank_account, $bank_owner, $user_id]);
        }
        
        echo json_encode(["success" => true]);
        exit;
    }
    
    if ($action === 'request_payout') {
        $amount = (float)($input['amount'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id, bank_name, bank_account, bank_owner FROM affiliates WHERE user_id = ? OR id = ?");
        $stmt->execute([$user_id, $payload->id]);
        $aff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aff) {
            http_response_code(400); echo json_encode(["error" => "Mitra afiliasi tidak ditemukan"]); exit;
        }
        
        // Check pending balance
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = ? AND status = 'pending'");
        $stmt->execute([$aff['id']]);
        $pending = (float)$stmt->fetchColumn();
        
        if ($amount < 50000 || $amount > $pending) {
            http_response_code(400); echo json_encode(["error" => "Nominal penarikan minimal Rp 50.000 dan tidak boleh melebihi saldo komisi"]); exit;
        }

        $bank_name = !empty($input['bank_name']) ? trim($input['bank_name']) : $aff['bank_name'];
        $bank_account = !empty($input['bank_account']) ? trim($input['bank_account']) : $aff['bank_account'];
        $bank_owner = !empty($input['bank_owner']) ? trim($input['bank_owner']) : $aff['bank_owner'];

        if (empty($bank_account)) {
            http_response_code(400); echo json_encode(["error" => "Harap lengkapi nomor rekening pencairan terlebih dahulu"]); exit;
        }
        
        $payout_id = bin2hex(random_bytes(16));
        $payout_id = substr($payout_id,0,8).'-'.substr($payout_id,8,4).'-'.substr($payout_id,12,4).'-'.substr($payout_id,16,4).'-'.substr($payout_id,20,12);
        
        $pdo->beginTransaction();
        try {
            // Auto create payouts table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `payouts` (
                `id` varchar(36) NOT NULL,
                `affiliate_id` varchar(36) NOT NULL,
                `amount` float NOT NULL,
                `status` varchar(20) DEFAULT 'pending',
                `bank_name` varchar(50) DEFAULT NULL,
                `bank_account` varchar(50) DEFAULT NULL,
                `bank_owner` varchar(100) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Create payout request
            $stmt = $pdo->prepare("INSERT INTO payouts (id, affiliate_id, amount, status, bank_name, bank_account, bank_owner, created_at) VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW())");
            $stmt->execute([$payout_id, $aff['id'], $amount, $bank_name, $bank_account, $bank_owner]);
            
            // FIFO Allocation: Mark pending commissions as requested up to the requested amount
            $stmt = $pdo->prepare("SELECT id, amount FROM commissions WHERE affiliate_id = ? AND status = 'pending' ORDER BY created_at ASC FOR UPDATE");
            $stmt->execute([$aff['id']]);
            $pendingComms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $accumulated = 0.0;
            $commIdsToUpdate = [];
            foreach ($pendingComms as $c) {
                if ($accumulated < $amount) {
                    $accumulated += (float)$c['amount'];
                    $commIdsToUpdate[] = $c['id'];
                }
            }

            if (!empty($commIdsToUpdate)) {
                $placeholders = implode(',', array_fill(0, count($commIdsToUpdate), '?'));
                $stmt = $pdo->prepare("UPDATE commissions SET status = 'requested' WHERE id IN ($placeholders)");
                $stmt->execute($commIdsToUpdate);
            }
            
            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Pengajuan penarikan dana berhasil dikirim"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal mengajukan penarikan: " . $e->getMessage()]);
        }
        exit;
    }

    // Join affiliate program (default POST action)
    $stmt = $pdo->prepare("SELECT id FROM affiliates WHERE user_id = ? OR id = ?");
    $stmt->execute([$user_id, $payload->id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Akun Anda sudah terdaftar sebagai mitra afiliasi"]);
        exit;
    }
    
    // Get student/user name to create referral code
    $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ? OR id = ? OR email = (SELECT identity_key FROM users WHERE id = ?)");
    $stmt->execute([$user_id, $payload->id, $user_id]);
    $name = !empty($input['name']) ? trim($input['name']) : (!empty($student['name']) ? $student['name'] : (!empty($input['bank_owner']) ? trim($input['bank_owner']) : 'Mitra'));
    // Clean name for code (alphanumeric uppercase)
    $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($name));
    $base_code = substr($clean_name, 0, 5) ?: 'EDUP';
    $random_suffix = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
    $referral_code = $base_code . $random_suffix;
    
    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
    
    $commission_rate = 20.00; // Default 20%
    
    // Bank info
    $bank_name = $input['bank_name'] ?? null;
    $bank_account = $input['bank_account'] ?? null;
    $bank_owner = $input['bank_owner'] ?? $name;
    
    try {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_name VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_account VARCHAR(50) NULL");
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN bank_owner VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN name VARCHAR(150) NULL");
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN email VARCHAR(150) NULL");
    } catch (Exception $e) {
        // Abaikan jika kolom sudah ada
    }

    // Get email for storage
    $reg_email = '';
    $eStmt = $pdo->prepare("SELECT identity_key FROM users WHERE id = ?");
    $eStmt->execute([$user_id]);
    $reg_email = $eStmt->fetchColumn() ?: '';

    try {
        $stmt = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, bank_name, bank_account, bank_owner, name, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $user_id, $tenant_id, $referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner, $name, $reg_email]);
        
        // Update user_roles so role is affiliate (not student)
        $pdo->prepare("UPDATE user_roles SET role = 'affiliate', reference_id = ? WHERE user_id = ?")->execute([$id, $user_id]);
        
        // Remove dummy student record so they do not show in students list
        $pdo->prepare("DELETE FROM students WHERE id = ? OR id = ? OR email = (SELECT identity_key FROM users WHERE id = ?)")->execute([$user_id, $payload->id, $user_id]);

        // Generate updated JWT token with role 'affiliate'
        $uEmail = $reg_email;

        $newToken = generate_jwt([
            'user_id' => $user_id,
            'id' => $id,
            'role' => 'affiliate',
            'tenant_id' => $tenant_id,
            'name' => $name
        ]);

        echo json_encode([
            "success" => true,
            "token" => $newToken,
            "referral_code" => $referral_code,
            "user" => [
                "id" => $user_id,
                "name" => $name,
                "email" => $uEmail,
                "role" => "affiliate"
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}
