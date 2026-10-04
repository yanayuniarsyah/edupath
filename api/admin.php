<?php
// api/admin.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'create_sa') {
    $sa_id = 'uat-u-superadmin';
    $sa_ref = 'uat-r-superadmin';
    $sa_email = 'superadmin@uat.edupath.local';
    $sa_pass = 'EduPathSuperAdmin01!2026';
    $sa_hash = password_hash($sa_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('$sa_id', '$sa_email', '$sa_hash', 1)");
    $pdo->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', '$sa_id', NULL, 'superadmin', '$sa_id')");
    
    $adm_id = 'uat-u-admin';
    $adm_ref = 'uat-r-admin';
    $adm_email = 'admin@uat.edupath.local';
    $adm_pass = 'EduPathAdmin01!2026';
    $adm_hash = password_hash($adm_pass, PASSWORD_BCRYPT);
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES ('$uat_tenant_id', 'UAT EduPath Tenant', 'uat-edupath', 1)");
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('$adm_id', '$adm_email', '$adm_hash', 1)");
    $pdo->exec("INSERT IGNORE INTO admins (id, tenant_id, username, password, name) VALUES ('$adm_ref', '$uat_tenant_id', '$adm_email', '$adm_hash', 'UAT Tenant Admin')");
    $pdo->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', '$adm_id', '$uat_tenant_id', 'admin', '$adm_ref')");
    
    echo "SA CREATED";
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting untuk admin login — 5 percobaan per 30 menit
    if (!check_rate_limit($pdo, 'admin_login', 5, 30)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan login. Coba lagi nanti."]);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    // Join with tenants to check if tenant is active (only for non-superadmin)
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, a.id as admin_id, t.is_active as tenant_active
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN admins a ON ur.reference_id = a.id
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE u.identity_key = ? AND u.is_active = 1 AND ur.role IN ('admin', 'superadmin')
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        if ($admin['role'] === 'admin' && (int)$admin['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }        reset_rate_limit($pdo, 'admin_login');
        $token = generate_jwt([
            'user_id' => $admin['user_id'], 
            'id' => $admin['reference_id'], 
            'role' => $admin['role'], 
            'tenant_id' => $admin['tenant_id'] // superadmin will naturally have NULL here
        ]);
        $csrf_token = generate_csrf_token();
        set_auth_cookies($token, $csrf_token);
        unset($admin['password']);
        echo json_encode(["token" => $token, "csrf_token" => $csrf_token, "user" => $admin]);
    } else {
        log_audit($pdo, null, null, 'admin_login_failed', null, ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        http_response_code(401);
        echo json_encode(["error" => "Username atau password salah"]);
    }
    exit;
}

// Untuk rute di bawah ini, wajib login sebagai admin atau superadmin
$payload = authenticate();
if (!in_array($payload->role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($payload->role === 'superadmin' && empty($payload->tenant_id)) {
        // Superadmin stats (Global across platform)
        $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
        $activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn() ?: 0;
        $totalQuizzes = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn() ?: 0;
        $totalRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE status IN ('paid', 'settlement')")->fetchColumn() ?: 0;
        $activeSubscriptions = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn() ?: 0;
        $totalAffiliates = $pdo->query("SELECT COUNT(*) FROM affiliates")->fetchColumn() ?: 0;
        $totalCommissions = $pdo->query("SELECT SUM(amount) FROM commissions WHERE status = 'paid'")->fetchColumn() ?: 0;
        $totalTenants = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn() ?: 0;

        $statsData = [
            "totalStudents" => (int)$totalStudents,
            "activeStudents" => (int)$activeStudents,
            "totalQuizzes" => (int)$totalQuizzes,
            "totalRevenue" => (float)$totalRevenue,
            "activeSubscriptions" => (int)$activeSubscriptions,
            "totalAffiliates" => (int)$totalAffiliates,
            "totalCommissions" => (float)$totalCommissions,
            "totalTenants" => (int)$totalTenants
        ];
        echo json_encode(array_merge($statsData, ["stats" => $statsData]));
        exit;
    }

    $tenant_id = $payload->tenant_id;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalStudents = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE is_active = 1 AND tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $activeStudents = $stmt->fetchColumn() ?: 0;

    $totalQuizzes = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM orders o JOIN students s ON o.student_id = s.id WHERE o.status IN ('paid', 'settlement') AND s.tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Total subscriptions (B2C + B2B within tenant)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM subscriptions s
        LEFT JOIN students st ON s.student_id = st.id
        WHERE (s.tenant_id = ? OR st.tenant_id = ?) AND s.status = 'active'
    ");
    $stmt->execute([$tenant_id, $tenant_id]);
    $activeSubscriptions = $stmt->fetchColumn() ?: 0;

    // Affiliate summary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM affiliates WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalAffiliates = $stmt->fetchColumn() ?: 0;

    // Commission summary (only paid)
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM commissions WHERE status = 'paid' AND tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalCommissions = $stmt->fetchColumn() ?: 0;

    $statsData = [
        "totalStudents" => (int)$totalStudents,
        "activeStudents" => (int)$activeStudents,
        "totalQuizzes" => (int)$totalQuizzes,
        "totalRevenue" => (float)$totalRevenue,
        "activeSubscriptions" => (int)$activeSubscriptions,
        "totalAffiliates" => (int)$totalAffiliates,
        "totalCommissions" => (float)$totalCommissions
    ];
    echo json_encode(array_merge($statsData, ["stats" => $statsData]));
    exit;
}
elseif ($action === 'students') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($payload->role === 'superadmin' && empty($payload->tenant_id)) {
            $stmt = $pdo->query("SELECT id, name, email, plan, is_active FROM students ORDER BY created_at DESC");
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, plan, is_active FROM students WHERE tenant_id = ? ORDER BY created_at DESC");
            $stmt->execute([$payload->tenant_id]);
        }
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["students" => $students]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $plan = $input['plan'] ?? 'free';
        
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Name, email, and password required"]); exit;
        }
        
        $user_id = bin2hex(random_bytes(16));
        $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
        
        $student_id = bin2hex(random_bytes(16));
        $student_id = substr($student_id,0,8).'-'.substr($student_id,8,4).'-'.substr($student_id,12,4).'-'.substr($student_id,16,4).'-'.substr($student_id,20,12);
        
        $tenant_id = $payload->tenant_id;
        if ($payload->role === 'superadmin' && !$tenant_id) {
            $tenant = $pdo->query("SELECT id FROM tenants WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $tenant_id = $tenant ? $tenant['id'] : null;
        }

        if (empty($tenant_id)) {
            http_response_code(400); echo json_encode(["error" => "Tenant ID tidak ditemukan"]); exit;
        }

        try {
            // Cek apakah email sudah terdaftar di users
            $check = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["error" => "Email sudah terdaftar"]);
                exit;
            }

            $pdo->beginTransaction();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $email, $hashed]);
            
            $stmt = $pdo->prepare("INSERT INTO students (id, tenant_id, name, email, password, plan, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$student_id, $tenant_id, $name, $email, $hashed, $plan]);
            
            $ur_id = bin2hex(random_bytes(16));
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($user_id,20,12);
            $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)");
            $stmt->execute([$ur_id, $user_id, $tenant_id, $student_id]);
            
            $pdo->commit();
            echo json_encode(["success" => true, "id" => $student_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat siswa", "details" => $e->getMessage()]);
        }
    }
}
elseif ($action === 'orders' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as student_name, s.email as student_email 
        FROM orders o 
        JOIN students s ON o.student_id = s.id 
        WHERE s.tenant_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$payload->tenant_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'affiliates' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.identity_key 
        FROM affiliates a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.tenant_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$payload->tenant_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'commissions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.*, a.referral_code, u.identity_key as affiliate_email, 
               o.plan_name as order_plan_name, s.name as student_name
        FROM commissions c
        JOIN affiliates a ON c.affiliate_id = a.id
        JOIN users u ON a.user_id = u.id
        JOIN orders o ON c.order_id = o.id
        JOIN students s ON o.student_id = s.id
        WHERE c.tenant_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$payload->tenant_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'payout_commission' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $commission_id = $input['commission_id'] ?? '';
    $payout_reference = $input['payout_reference'] ?? '';

    if (empty($commission_id)) {
        http_response_code(400); echo json_encode(["error" => "Commission ID required"]); exit;
    }

    try {
        $pdo->beginTransaction();
        
        // Lock commission row
        $stmt = $pdo->prepare("SELECT status FROM commissions WHERE id = ? AND tenant_id = ? FOR UPDATE");
        $stmt->execute([$commission_id, $payload->tenant_id]);
        $comm = $stmt->fetch();
        
        if (!$comm) {
            $pdo->rollBack();
            http_response_code(404); echo json_encode(["error" => "Commission not found"]); exit;
        }
        
        if ($comm['status'] === 'paid') {
            $pdo->rollBack();
            http_response_code(400); echo json_encode(["error" => "Commission already paid"]); exit;
        }

        // Update to paid with audit trail
        $update = $pdo->prepare("
            UPDATE commissions 
            SET status = 'paid', 
                paid_by = ?, 
                paid_at = NOW(), 
                payout_reference = ? 
            WHERE id = ?
        ");
        $update->execute([$payload->user_id, $payout_reference, $commission_id]);
        
        $pdo->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500); echo json_encode(["error" => "Database error"]);
    }
}
elseif ($action === 'tenants') {
    if ($payload->role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(["error" => "System admin only"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($pdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $admin_email = trim($input['admin_email'] ?? '');
        $admin_name = trim($input['admin_name'] ?? '');
        $admin_password = $input['admin_password'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            // 1. Create Tenant
            $stmt = $pdo->prepare("INSERT INTO tenants (id, name, slug, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$id, $input['name'], $input['slug']]);
            
            // 2. Provision First Admin (if provided)
            if (!empty($admin_email) && !empty($admin_password)) {
                $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
                
                // Create user identity
                $user_id = bin2hex(random_bytes(16));
                $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$user_id, $admin_email, $hashed_password]);
                
                // Create admin profile
                $admin_id = bin2hex(random_bytes(16));
                $admin_id = substr($admin_id,0,8).'-'.substr($admin_id,8,4).'-'.substr($admin_id,12,4).'-'.substr($admin_id,16,4).'-'.substr($admin_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO admins (id, username, password, name) VALUES (?, ?, ?, ?)");
                // Admin profile legacy password field is populated for compatibility
                $stmt->execute([$admin_id, $admin_email, $hashed_password, $admin_name ?: 'Admin']);
                
                // Link Role
                $ur_id = bin2hex(random_bytes(16));
                $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'admin', ?)");
                $stmt->execute([$ur_id, $user_id, $id, $admin_id]);
            }
            
            $pdo->commit();
            log_audit($pdo, $payload->user_id, null, 'tenant_created', $id, ['name' => $input['name'], 'slug' => $input['slug']]);
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal membuat tenant", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $stmt = $pdo->prepare("UPDATE tenants SET name=?, slug=?, is_active=? WHERE id=?");
        $stmt->execute([$input['name'], $input['slug'], $is_active, $id]);
        
        log_audit($pdo, $payload->user_id, null, 'tenant_updated', $id, ['is_active' => $is_active]);
        
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'entitlements_dictionary') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            ['key' => 'tryout_unlimited', 'label' => 'Tryout Tanpa Batas'],
            ['key' => 'ai_adaptive_path', 'label' => 'Jalur Belajar AI Adaptif'],
            ['key' => 'premium_materials', 'label' => 'Materi Premium'],
            ['key' => 'feature_quiz', 'label' => 'Akses Kuis Reguler']
        ]);
    }
}
elseif ($action === 'products') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
    }
}
elseif ($action === 'plans') {
    $VALID_ENTITLEMENTS = ['tryout_unlimited', 'ai_adaptive_path', 'premium_materials', 'feature_quiz'];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($plans)) {
            // Auto-seed official 3 packages exactly matching Landing Page
            $defaultPlans = [
                [
                    'id' => 'plan-mandiri',
                    'name' => 'Paket Mandiri',
                    'price' => 180000,
                    'duration' => 30,
                    'features' => json_encode([
                        '500+ Micro-Lessons Adaptif (TPS & Literasi)',
                        '50.000+ Bank Soal HOTS dengan Standar Skor IRT',
                        '5x Tryout Nasional / Bulan + Pembahasan Lengkap',
                        'Radar Deteksi Blind-Spot Belajar Instan',
                        'Habit Tracker: Weekly Learning Check-in (WLC)'
                    ])
                ],
                [
                    'id' => 'plan-utama',
                    'name' => 'Paket Utama',
                    'price' => 450000,
                    'duration' => 30,
                    'features' => json_encode([
                        'Semua Fitur di Paket Mandiri',
                        'AI Tutor Companion 24/7 (Bimbingan logika tanpa batas)',
                        'Unlimited Simulasi IRT Adaptif (Bebas TO tanpa kuota)',
                        'Estimasi Kesiapan & Rasionalisasi Prodi Edukatif',
                        'Laporan Progres Belajar Otomatis via WhatsApp Orang Tua'
                    ])
                ],
                [
                    'id' => 'plan-vip',
                    'name' => 'Paket VIP',
                    'price' => 1100000,
                    'duration' => 30,
                    'features' => json_encode([
                        'Semua Fitur Lengkap di Paket Utama',
                        '1-on-1 Private Mentoring Mingguan via Zoom (60 Menit)',
                        'Grup WhatsApp VIP Langsung bareng Mentor Senior',
                        'Audit Portofolio Belajar & Siasat Prodi Pilihan'
                    ])
                ]
            ];
            $stmtIns = $pdo->prepare("INSERT INTO plans (id, name, price, duration, features) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), duration=VALUES(duration), features=VALUES(features)");
            foreach ($defaultPlans as $dp) {
                $stmtIns->execute([$dp['id'], $dp['name'], $dp['price'], $dp['duration'], $dp['features']]);
            }
            $plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($plans as &$plan) {
            $stmt = $pdo->prepare("SELECT feature_key FROM plan_entitlements WHERE plan_id = ?");
            $stmt->execute([$plan['id']]);
            $features = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($features)) {
                $plan['features'] = json_encode($features);
            }
        }
        echo json_encode($plans);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $features = is_array($input['features']) ? $input['features'] : [];
        $valid_features = array_intersect($features, $VALID_ENTITLEMENTS);
        $discount = isset($input['discount']) ? (float)$input['discount'] : 0.00;
        $product_id = !empty($input['product_id']) ? $input['product_id'] : null;
        $billing_cycle = $input['billing_cycle'] ?? 'monthly';
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO plans (id, tenant_id, name, price, discount, duration, features, product_id, billing_cycle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, null, $input['name'], $input['price'], $discount, $input['duration'], json_encode(array_values($valid_features)), $product_id, $billing_cycle]);
            
            $stmt_ent = $pdo->prepare("INSERT IGNORE INTO plan_entitlements (id, plan_id, feature_key) VALUES (UUID(), ?, ?)");
            foreach ($valid_features as $fk) {
                $stmt_ent->execute([$id, $fk]);
            }
            $pdo->commit();
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat plan", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = $input['id'] ?? '';
        $features = is_array($input['features']) ? $input['features'] : [];
        $valid_features = array_intersect($features, $VALID_ENTITLEMENTS);
        
        $discount = isset($input['discount']) ? (float)$input['discount'] : 0.00;
        $product_id = !empty($input['product_id']) ? $input['product_id'] : null;
        $billing_cycle = $input['billing_cycle'] ?? 'monthly';
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE plans SET name=?, price=?, discount=?, duration=?, features=?, product_id=?, billing_cycle=? WHERE id=?");
            $stmt->execute([$input['name'], $input['price'], $discount, $input['duration'], json_encode(array_values($valid_features)), $product_id, $billing_cycle, $id]);
            
            $pdo->prepare("DELETE FROM plan_entitlements WHERE plan_id = ?")->execute([$id]);
            
            $stmt_ent = $pdo->prepare("INSERT IGNORE INTO plan_entitlements (id, plan_id, feature_key) VALUES (UUID(), ?, ?)");
            foreach ($valid_features as $fk) {
                $stmt_ent->execute([$id, $fk]);
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal update plan", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = $_GET['id'] ?? '';
        $pdo->prepare("DELETE FROM plans WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'questions') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($pdo->query("SELECT * FROM questions ORDER BY created_at DESC")->fetchAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $stmt = $pdo->prepare("INSERT INTO questions (id, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference, is_qc_passed, classification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $sub_materi = $input['sub_materi'] ?? $input['subtes'] ?? '';
        $is_qc_passed = isset($input['is_qc_passed']) && $input['is_qc_passed'] ? 1 : 0;
        $classification = strtoupper($input['usage_type'] ?? 'LATIHAN');
        $stmt->execute([
            $id, $sub_materi, $input['bab']??null, $input['difficulty']??'medium', 
            $input['question'], $input['option_a'], $input['option_b'], $input['option_c'], 
            $input['option_d'], $input['option_e']??null, $input['correct'], $input['explanation']??null,
            $input['cognitive_demand']??null, $input['source_type']??'author_created', $input['rights_status']??'unknown',
            $input['source_name']??null, $input['source_year']??null, $input['source_reference']??null, $is_qc_passed, $classification
        ]);
        echo json_encode(["success" => true, "id" => $id]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = $input['id'] ?? '';
        $stmt = $pdo->prepare("UPDATE questions SET sub_materi=?, bab=?, difficulty=?, question=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?, correct=?, explanation=?, cognitive_demand=?, source_type=?, rights_status=?, source_name=?, source_year=?, source_reference=?, is_qc_passed=?, classification=? WHERE id=?");
        $sub_materi = $input['sub_materi'] ?? $input['subtes'] ?? '';
        $is_qc_passed = isset($input['is_qc_passed']) && $input['is_qc_passed'] ? 1 : 0;
        $classification = strtoupper($input['usage_type'] ?? 'LATIHAN');
        $stmt->execute([
            $sub_materi, $input['bab']??null, $input['difficulty']??'medium', 
            $input['question'], $input['option_a'], $input['option_b'], $input['option_c'], 
            $input['option_d'], $input['option_e']??null, $input['correct'], $input['explanation']??null,
            $input['cognitive_demand']??null, $input['source_type']??'author_created', $input['rights_status']??'unknown',
            $input['source_name']??null, $input['source_year']??null, $input['source_reference']??null, $is_qc_passed, $classification,
            $id
        ]);
        echo json_encode(["success" => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'staff') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        
        $sql = "
            SELECT u.identity_key as username, a.name, a.id, ur.role
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN admins a ON ur.reference_id = a.id
            WHERE ur.role != 'superadmin'
        ";
        $params = [];
        if ($tenant_id) {
            $sql .= " AND ur.tenant_id = ?";
            $params[] = $tenant_id;
        } else {
            $sql .= " AND ur.tenant_id IS NULL";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["staff" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($input['username'] ?? '');
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Username dan password diperlukan"]); exit;
        }

        $tenant_id = $payload->tenant_id;
        if (empty($tenant_id)) {
            $tenant_id = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
            if (!$tenant_id) {
                $tenant_id = 'default-tenant';
                $pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES ('$tenant_id', 'EduPath Indonesia', 'edupath-master', 1)");
            }
        }
        if (!in_array($role, ['admin', 'teacher'])) $role = 'teacher';

        try {
            $pdo->beginTransaction();
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $user_id = bin2hex(random_bytes(16));
            $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $username, $hashed_password]);
            
            $admin_id = bin2hex(random_bytes(16));
            $admin_id = substr($admin_id,0,8).'-'.substr($admin_id,8,4).'-'.substr($admin_id,12,4).'-'.substr($admin_id,16,4).'-'.substr($admin_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO admins (id, tenant_id, username, password, name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$admin_id, $tenant_id, $username, $hashed_password, $name]);
            
            $ur_id = bin2hex(random_bytes(16));
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ur_id, $user_id, $tenant_id, $role, $admin_id]);
            
            $pdo->commit();
            log_audit($pdo, $payload->user_id, $tenant_id, 'staff_created', $admin_id, ['username' => $username, 'role' => $role]);
            echo json_encode(["success" => true, "id" => $admin_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat staff", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        
        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        $scope = $tenant_id ? " AND ur.tenant_id = ?" : " AND ur.tenant_id IS NULL";
        $stmt = $pdo->prepare("UPDATE admins a JOIN user_roles ur ON ur.reference_id = a.id SET a.name = ? WHERE a.id = ? AND ur.role != 'superadmin' $scope");
        $params = [$name, $id];
        if ($tenant_id) $params[] = $tenant_id;
        $stmt->execute($params);

        $stmt = $pdo->prepare("UPDATE user_roles SET role = ? WHERE reference_id = ? AND role != 'superadmin' $scope");
        $params = [$role, $id];
        if ($tenant_id) $params[] = $tenant_id;
        $stmt->execute($params);
        
        if (!empty($input['password'])) {
            $hashed = password_hash($input['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users u JOIN user_roles ur ON u.id = ur.user_id SET u.password = ? WHERE ur.reference_id = ? AND ur.role != 'superadmin' $scope");
            $params = [$hashed, $id];
            if ($tenant_id) $params[] = $tenant_id;
            $stmt->execute($params);
            $stmt = $pdo->prepare("UPDATE admins a JOIN user_roles ur ON ur.reference_id = a.id SET a.password = ? WHERE a.id = ? AND ur.role != 'superadmin' $scope");
            $params = [$hashed, $id];
            if ($tenant_id) $params[] = $tenant_id;
            $stmt->execute($params);
        }
        echo json_encode(["success" => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? '';
        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        $scope = $tenant_id ? " AND ur.tenant_id = ?" : " AND ur.tenant_id IS NULL";
        $stmt = $pdo->prepare("DELETE u FROM users u JOIN user_roles ur ON ur.user_id = u.id WHERE ur.reference_id = ? AND ur.role != 'superadmin' $scope");
        $params = [$id];
        if ($tenant_id) $params[] = $tenant_id;
        $stmt->execute($params);
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'commissions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
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
}
elseif ($action === 'payout_commission' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $comm_id = $input['commission_id'] ?? '';
    try {
        $stmt = $pdo->prepare("UPDATE commissions SET status = 'paid' WHERE id = ?");
        $stmt->execute([$comm_id]);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal memperbarui status komisi"]);
    }
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint admin tidak ditemukan"]);
}
?>
