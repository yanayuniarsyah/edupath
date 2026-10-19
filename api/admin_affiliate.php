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
    } else {
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
                        (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission
                    FROM affiliates a
                    LEFT JOIN users u ON a.user_id = u.id
                    LEFT JOIN user_roles ur ON (u.id = ur.user_id AND ur.role = 'student')
                    LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
                    WHERE a.user_id = ? OR a.id = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$payload->user_id, $payload->id]);
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
                        (SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE affiliate_id = a.id AND status = 'pending') as pending_commission
                    FROM affiliates a
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

            $stmt = $pdo->prepare("UPDATE commissions SET status = 'requested' WHERE affiliate_id = ? AND status = 'pending'");
            $stmt->execute([$aff['id']]);

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
    } elseif ($action === 'reject_payout') {
        $payout_id = $input['payout_id'] ?? '';
        $reason = $input['reason'] ?? 'Ditolak oleh admin';
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM payouts WHERE id = ? AND status = 'pending'");
            $stmt->execute([$payout_id]);
            $payout = $stmt->fetch();
            
            if (!$payout) {
                http_response_code(404);
                echo json_encode(["error" => "Payout tidak ditemukan atau sudah diproses"]);
                $pdo->rollBack();
                exit;
            }
            
            // Mark payout as rejected
            $stmt = $pdo->prepare("UPDATE payouts SET status = 'rejected', notes = ? WHERE id = ?");
            $stmt->execute([$reason, $payout_id]);
            
            // Revert requested commissions back to pending
            $stmt = $pdo->prepare("UPDATE commissions SET status = 'pending' WHERE affiliate_id = ? AND status = 'requested'");
            $stmt->execute([$payout['affiliate_id']]);
            
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

            $stmtAff = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, bank_name, bank_account, bank_owner, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmtAff->execute([$aff_id, $user_id, $payload->tenant_id ?: 'default_tenant', $referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner]);

            $ur_id = 'ur-' . substr(bin2hex(random_bytes(8)), 0, 10);
            $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'affiliate', ?) ON DUPLICATE KEY UPDATE role = 'affiliate', reference_id = VALUES(reference_id)")
                ->execute([$ur_id, $user_id, $payload->tenant_id ?: 'default_tenant', $aff_id]);

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

        if (empty($id) || empty($referral_code)) {
            http_response_code(400); echo json_encode(["error" => "ID dan kode referral wajib"]); exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE affiliates SET referral_code = ?, commission_rate = ?, bank_name = ?, bank_account = ?, bank_owner = ? WHERE id = ?");
            $stmt->execute([$referral_code, $commission_rate, $bank_name, $bank_account, $bank_owner, $id]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update afiliasi"]);
        }
    } elseif ($action === 'delete_affiliate') {
        $id = $input['id'] ?? $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID afiliasi wajib"]); exit;
        }
        $pdo->prepare("DELETE FROM affiliates WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
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
?>
