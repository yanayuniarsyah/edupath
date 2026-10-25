<?php
// api/admin_affiliate.php
require_once 'config.php';
require_once 'jwt.php';

$payload = authenticate();

if (!in_array($payload->role, ['admin', 'superadmin', 'affiliate'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'payouts') {
        try {
            if ($payload->role === 'affiliate') {
                $stmt = $pdo->prepare("
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
                    WHERE a.user_id = ? OR a.id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$payload->user_id, $payload->id]);
            } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
                $stmt = $pdo->prepare("
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
                    WHERE a.tenant_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$payload->tenant_id]);
            } else {
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
            }
            $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($payouts);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } elseif ($action === 'commissions') {
        try {
            if ($payload->role === 'affiliate') {
                $stmt = $pdo->prepare("
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
                    WHERE a.user_id = ? OR a.id = ?
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$payload->user_id, $payload->id]);
            } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
                $stmt = $pdo->prepare("
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
                    WHERE (c.tenant_id = ? OR a.tenant_id = ?)
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$payload->tenant_id, $payload->tenant_id]);
            } else {
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
            }
            $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($commissions);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } elseif ($action === 'list') {
        // list affiliates
        try {
            if ($payload->role === 'affiliate') {
                $stmt = $pdo->prepare("
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
                        (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission,
                        t.name as tenant_name
                    FROM affiliates a
                    LEFT JOIN tenants t ON a.tenant_id = t.id
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                    LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                    WHERE a.user_id = ? OR a.id = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$payload->user_id, $payload->id]);
            } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
                $stmt = $pdo->prepare("
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
                        (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission,
                        t.name as tenant_name
                    FROM affiliates a
                    LEFT JOIN tenants t ON a.tenant_id = t.id
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                    LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                    WHERE a.tenant_id = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$payload->tenant_id]);
            } else {
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
                        (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission,
                        t.name as tenant_name
                    FROM affiliates a
                    LEFT JOIN tenants t ON a.tenant_id = t.id
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                    LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                    ORDER BY a.created_at DESC
                ");
            }
            $affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($affiliates);
        } catch (PDOException $e) {
            error_log("Affiliates query failed: " . $e->getMessage());
            echo json_encode([]);
        }
    } elseif ($action === 'financial_ledger') {
        try {
            if ($payload->role === 'affiliate') {
                $stmt = $pdo->prepare("
                    SELECT * FROM financial_ledger 
                    WHERE party_type = 'AFFILIATE' AND party_id = ? 
                    ORDER BY created_at DESC LIMIT 100
                ");
                $stmt->execute([$payload->id]);
            } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
                $stmt = $pdo->prepare("
                    SELECT * FROM financial_ledger 
                    WHERE tenant_id = ? OR (party_type = 'BRANCH' AND party_id = ?)
                    ORDER BY created_at DESC LIMIT 200
                ");
                $stmt->execute([$payload->tenant_id, $payload->tenant_id]);
            } else {
                $stmt = $pdo->query("SELECT * FROM financial_ledger ORDER BY created_at DESC LIMIT 300");
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } elseif ($action === 'branch_performance') {
        try {
            $tenantId = ($payload->role !== 'superadmin') ? $payload->tenant_id : ($_GET['tenant_id'] ?? null);
            if (!$tenantId) {
                http_response_code(400);
                echo json_encode(["error" => "Tenant ID diperlukan"]);
                exit;
            }

            // 1. Total Affiliators
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM affiliates WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
            $totalAffiliators = (int)$stmt->fetchColumn();

            // 2. Gross Sales through branch's affiliates
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(amount), 0) as gross_sales,
                    COUNT(*) as total_orders
                FROM orders 
                WHERE branch_tenant_id = ? AND status = 'paid'
            ");
            $stmt->execute([$tenantId]);
            $sales = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Branch Net Revenue
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM financial_ledger 
                WHERE party_type = 'BRANCH' AND party_id = ? AND entry_type = 'BRANCH_NET_REVENUE' AND status = 'posted'
            ");
            $stmt->execute([$tenantId]);
            $branchNet = (float)$stmt->fetchColumn();

            // 4. Affiliate Commissions Deducted from Branch
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(c.amount), 0) 
                FROM commissions c 
                JOIN affiliates a ON c.affiliate_id = a.id 
                WHERE a.tenant_id = ? AND c.status = 'paid'
            ");
            $stmt->execute([$tenantId]);
            $affCommissionsPaid = (float)$stmt->fetchColumn();

            echo json_encode([
                "tenant_id"                => $tenantId,
                "total_affiliators"        => $totalAffiliators,
                "gross_sales"              => (float)($sales['gross_sales'] ?? 0),
                "total_paid_orders"        => (int)($sales['total_orders'] ?? 0),
                "branch_net_revenue"       => $branchNet,
                "affiliate_commissions_cut"=> $affCommissionsPaid
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    } elseif ($action === 'royalty_summary') {
        try {
            if ($payload->role !== 'superadmin') {
                http_response_code(403);
                echo json_encode(["error" => "Hanya Superadmin yang dapat melihat ringkasan royalti"]);
                exit;
            }
            $stmt = $pdo->query("
                SELECT 
                    co.id as owner_id, co.name as owner_name, co.email,
                    COUNT(ra.id) as total_accruals,
                    COALESCE(SUM(ra.total_amount), 0) as total_royalty_accrued,
                    COALESCE(SUM(CASE WHEN ra.status = 'settled' THEN ra.total_amount ELSE 0 END), 0) as settled_amount,
                    COALESCE(SUM(CASE WHEN ra.status = 'pending' THEN ra.total_amount ELSE 0 END), 0) as pending_amount
                FROM content_owners co
                LEFT JOIN royalty_accruals ra ON co.id = ra.owner_id
                GROUP BY co.id, co.name, co.email
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $action = $input['action'] ?? '';
    
    if ($payload->role === 'affiliate' && !in_array($action, ['request_payout', 'update_bank'])) {
        http_response_code(403);
        echo json_encode(["error" => "Mitra afiliasi tidak memiliki izin untuk tindakan ini"]);
        exit;
    }

    if ($action === 'request_payout') {
        $amount = (float)($input['amount'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, bank_name, bank_account, bank_owner FROM affiliates WHERE user_id = ? OR id = ?");
        $stmt->execute([$payload->user_id, $payload->id]);
        $aff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$aff) {
            http_response_code(400); echo json_encode(["error" => "Mitra afiliasi tidak ditemukan"]); exit;
        }

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
    
    if ($action === 'approve_payout') {
        $payout_id = $input['payout_id'] ?? '';
        
        $pdo->beginTransaction();
        try {
            // Get payout
            $stmt = $pdo->prepare("SELECT p.*, a.tenant_id as aff_tenant FROM payouts p JOIN affiliates a ON p.affiliate_id = a.id WHERE p.id = ? AND p.status = 'pending'");
            $stmt->execute([$payout_id]);
            $payout = $stmt->fetch();
            
            if (!$payout) {
                http_response_code(404);
                echo json_encode(["error" => "Payout not found or already processed"]);
                $pdo->rollBack();
                exit;
            }

            if ($payload->role !== 'superadmin' && !empty($payload->tenant_id) && $payout['aff_tenant'] !== $payload->tenant_id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda tidak memiliki izin memproses payout cabang lain"]);
                $pdo->rollBack();
                exit;
            }
            
            // Mark payout as paid
            $stmt = $pdo->prepare("UPDATE payouts SET status = 'paid' WHERE id = ?");
            $stmt->execute([$payout_id]);
            
            // Mark requested commissions as paid up to the payout amount
            $stmt = $pdo->prepare("SELECT id, amount FROM commissions WHERE affiliate_id = ? AND status = 'requested' ORDER BY created_at ASC FOR UPDATE");
            $stmt->execute([$payout['affiliate_id']]);
            $requestedComms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $acc = 0.0;
            $idsPaid = [];
            foreach ($requestedComms as $c) {
                if ($acc < (float)$payout['amount']) {
                    $acc += (float)$c['amount'];
                    $idsPaid[] = $c['id'];
                }
            }
            if (!empty($idsPaid)) {
                $placeholders = implode(',', array_fill(0, count($idsPaid), '?'));
                $stmt = $pdo->prepare("UPDATE commissions SET status = 'paid' WHERE id IN ($placeholders)");
                $stmt->execute($idsPaid);
            }
            
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    } elseif ($action === 'reject_payout') {
        $payout_id = $input['payout_id'] ?? '';
        $reason = $input['reason'] ?? 'Ditolak oleh admin';
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT p.*, a.tenant_id as aff_tenant FROM payouts p JOIN affiliates a ON p.affiliate_id = a.id WHERE p.id = ? AND p.status = 'pending'");
            $stmt->execute([$payout_id]);
            $payout = $stmt->fetch();
            
            if (!$payout) {
                http_response_code(404);
                echo json_encode(["error" => "Payout tidak ditemukan atau sudah diproses"]);
                $pdo->rollBack();
                exit;
            }

            if ($payload->role !== 'superadmin' && !empty($payload->tenant_id) && $payout['aff_tenant'] !== $payload->tenant_id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda tidak memiliki izin menolak payout cabang lain"]);
                $pdo->rollBack();
                exit;
            }
            
            // Mark payout as rejected
            $stmt = $pdo->prepare("UPDATE payouts SET status = 'rejected', notes = ? WHERE id = ?");
            $stmt->execute([$reason, $payout_id]);
            
            // Revert requested commissions back to pending up to the payout amount
            $stmt = $pdo->prepare("SELECT id, amount FROM commissions WHERE affiliate_id = ? AND status = 'requested' ORDER BY created_at ASC FOR UPDATE");
            $stmt->execute([$payout['affiliate_id']]);
            $requestedComms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $acc = 0.0;
            $idsRevert = [];
            foreach ($requestedComms as $c) {
                if ($acc < (float)$payout['amount']) {
                    $acc += (float)$c['amount'];
                    $idsRevert[] = $c['id'];
                }
            }
            if (!empty($idsRevert)) {
                $placeholders = implode(',', array_fill(0, count($idsRevert), '?'));
                $stmt = $pdo->prepare("UPDATE commissions SET status = 'pending' WHERE id IN ($placeholders)");
                $stmt->execute($idsRevert);
            }
            
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
    } elseif ($action === 'create_affiliate') {
        $email = trim($input['email'] ?? '');
        $name = trim($input['name'] ?? '');
        $referral_code = strtoupper(trim($input['referral_code'] ?? ''));
        $commission_rate = (float)($input['commission_rate'] ?? 20.0);
        $bank_name = trim($input['bank_name'] ?? '');
        $bank_account = trim($input['bank_account'] ?? '');
        $bank_owner = trim($input['bank_owner'] ?? $name);

        if (empty($email) || empty($referral_code)) {
            http_response_code(400); echo json_encode(["error" => "Email dan kode referral wajib diisi"]); exit;
        }

        try {
            $pdo->beginTransaction();
            // Check if user exists or create student/user
            $stmtU = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
            $stmtU->execute([$email]);
            $user = $stmtU->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = $user['id'];
            } else {
                $user_id = bin2hex(random_bytes(16));
                $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
                $defPass = password_hash('MitraEduPath2026!', PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$user_id, $email, $defPass]);
            }

            // Check if referral code taken
            $stmtRef = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ?");
            $stmtRef->execute([$referral_code]);
            if ($stmtRef->fetch()) {
                http_response_code(409); echo json_encode(["error" => "Kode referral sudah dipakai mitra lain"]); $pdo->rollBack(); exit;
            }

            $aff_id = bin2hex(random_bytes(16));
            $aff_id = substr($aff_id,0,8).'-'.substr($aff_id,8,4).'-'.substr($aff_id,12,4).'-'.substr($aff_id,16,4).'-'.substr($aff_id,20,12);

            if ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
                $aff_tenant_id = $payload->tenant_id;
            } else {
                $aff_tenant_id = !empty($input['tenant_id']) ? $input['tenant_id'] : ($payload->tenant_id ?: null);
                if (empty($aff_tenant_id)) {
                    $aff_tenant_id = $pdo->query("SELECT id FROM tenants WHERE is_active = 1 LIMIT 1")->fetchColumn() ?: null;
                }
            }

            $stmtAff = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, bank_name, bank_account, bank_owner, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmtAff->execute([$aff_id, $user_id, $aff_tenant_id, $referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner]);

            $ur_id = 'ur-' . substr(bin2hex(random_bytes(8)), 0, 10);
            $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'affiliate', ?) ON DUPLICATE KEY UPDATE role = 'affiliate', reference_id = VALUES(reference_id)")
                ->execute([$ur_id, $user_id, $aff_tenant_id, $aff_id]);

            $pdo->commit();
            echo json_encode(["success" => true, "id" => $aff_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat mitra afiliasi", "details" => $e->getMessage()]);
        }
    } elseif ($action === 'update_affiliate') {
        $id = $input['id'] ?? '';
        $referral_code = strtoupper(trim($input['referral_code'] ?? ''));
        $commission_rate = (float)($input['commission_rate'] ?? 20.0);
        $bank_name = trim($input['bank_name'] ?? '');
        $bank_account = trim($input['bank_account'] ?? '');
        $bank_owner = trim($input['bank_owner'] ?? '');
        $aff_tenant_id = array_key_exists('tenant_id', $input) ? (!empty($input['tenant_id']) ? $input['tenant_id'] : null) : false;

        if (empty($id) || empty($referral_code)) {
            http_response_code(400); echo json_encode(["error" => "ID dan kode referral wajib"]); exit;
        }

        if ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
            $chk = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ?");
            $chk->execute([$id]);
            $currentAffTenant = $chk->fetchColumn();
            if ($currentAffTenant !== $payload->tenant_id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat mengedit mitra pada cabang Anda"]);
                exit;
            }
        }

        try {
            if ($aff_tenant_id !== false && $payload->role === 'superadmin') {
                $stmt = $pdo->prepare("UPDATE affiliates SET referral_code = ?, commission_rate = ?, bank_name = ?, bank_account = ?, bank_owner = ?, tenant_id = ? WHERE id = ?");
                $stmt->execute([$referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner, $aff_tenant_id, $id]);
                $pdo->prepare("UPDATE user_roles SET tenant_id = ? WHERE reference_id = ? AND role = 'affiliate'")->execute([$aff_tenant_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE affiliates SET referral_code = ?, commission_rate = ?, bank_name = ?, bank_account = ?, bank_owner = ? WHERE id = ?");
                $stmt->execute([$referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner, $id]);
            }
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update afiliasi"]);
        }
    } elseif ($action === 'delete_affiliate') {
        $id = $input['id'] ?? $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID afiliasi wajib"]); exit;
        }

        if ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
            $chk = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ?");
            $chk->execute([$id]);
            $currentAffTenant = $chk->fetchColumn();
            if ($currentAffTenant !== $payload->tenant_id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat menghapus mitra pada cabang Anda"]);
                exit;
            }
        }

        $pdo->prepare("DELETE FROM affiliates WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
    } elseif ($action === 'payout_commission') {
        $comm_id = $input['commission_id'] ?? '';
        $ref = $input['payout_reference'] ?? 'MANUAL_TRANSFER';

        if ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
            $chk = $pdo->prepare("SELECT c.tenant_id, a.tenant_id as aff_tenant FROM commissions c LEFT JOIN affiliates a ON c.affiliate_id = a.id WHERE c.id = ?");
            $chk->execute([$comm_id]);
            $cRow = $chk->fetch(PDO::FETCH_ASSOC);
            if ($cRow && $cRow['tenant_id'] !== $payload->tenant_id && $cRow['aff_tenant'] !== $payload->tenant_id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda tidak memiliki akses ke komisi cabang lain"]);
                exit;
            }
        }

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
