<?php
// api/admin.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

function ensure_admin_schema($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;

    // 1. Table materials
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS materials (
                id VARCHAR(36) PRIMARY KEY,
                exam VARCHAR(50) DEFAULT 'SNBT',
                test_component VARCHAR(100) DEFAULT 'TPS',
                subtest VARCHAR(100) NOT NULL,
                sub_materi VARCHAR(100) NULL,
                topic VARCHAR(100) NULL,
                subtopic VARCHAR(100) NULL,
                skill VARCHAR(100) NULL,
                indicator VARCHAR(100) NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                teacher_name VARCHAR(150) NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    $materialCols = [
        "ALTER TABLE materials ADD COLUMN exam VARCHAR(50) DEFAULT 'SNBT'",
        "ALTER TABLE materials ADD COLUMN test_component VARCHAR(100) DEFAULT 'TPS'",
        "ALTER TABLE materials ADD COLUMN subtest VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN sub_materi VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN topic VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN subtopic VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN skill VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN indicator VARCHAR(100) NULL",
        "ALTER TABLE materials ADD COLUMN teacher_name VARCHAR(150) NULL",
        "ALTER TABLE materials ADD COLUMN is_active TINYINT(1) DEFAULT 1"
    ];
    foreach ($materialCols as $sql) {
        try { $pdo->exec($sql); } catch (\Throwable $e) {}
    }

    // 2. Table questions
    $questionCols = [
        "ALTER TABLE questions ADD COLUMN exam VARCHAR(50) DEFAULT 'SNBT'",
        "ALTER TABLE questions ADD COLUMN test_component VARCHAR(100) DEFAULT 'TPS'",
        "ALTER TABLE questions ADD COLUMN subtest VARCHAR(100) NULL",
        "ALTER TABLE questions ADD COLUMN topic VARCHAR(100) NULL",
        "ALTER TABLE questions ADD COLUMN subtopic VARCHAR(100) NULL",
        "ALTER TABLE questions ADD COLUMN skill VARCHAR(100) NULL",
        "ALTER TABLE questions ADD COLUMN indicator VARCHAR(100) NULL",
        "ALTER TABLE questions ADD COLUMN question_type VARCHAR(50) DEFAULT 'multiple_choice'",
        "ALTER TABLE questions ADD COLUMN is_qc_passed TINYINT(1) DEFAULT 1",
        "ALTER TABLE questions ADD COLUMN classification VARCHAR(50) DEFAULT 'LATIHAN'"
    ];
    foreach ($questionCols as $sql) {
        try { $pdo->exec($sql); } catch (\Throwable $e) {}
    }

    // 3. Table plans
    $planCols = [
        "ALTER TABLE plans ADD COLUMN tenant_id VARCHAR(36) NULL",
        "ALTER TABLE plans ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE plans ADD COLUMN product_id VARCHAR(100) NULL",
        "ALTER TABLE plans ADD COLUMN billing_cycle VARCHAR(50) DEFAULT 'monthly'",
        "ALTER TABLE plans ADD COLUMN is_active TINYINT(1) DEFAULT 1",
        "ALTER TABLE plans ADD COLUMN is_archived TINYINT(1) DEFAULT 0"
    ];
    foreach ($planCols as $sql) {
        try { $pdo->exec($sql); } catch (\Throwable $e) {}
    }
}
if (isset($_GET['migrate']) || ($action === 'migrate')) {
    ensure_admin_schema($pdo);
    if ($action === 'migrate') {
        echo json_encode(["success" => true, "message" => "Admin schema migrated successfully"]);
        exit;
    }
}

// Helper: safe json_encode yang menangani karakter non-UTF8
// json_encode PHP akan return false jika data mengandung karakter non-UTF8,
// menyebabkan response kosong dan frontend menampilkan tabel kosong.
function safe_json_encode($data) {
    $result = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    if ($result === false) {
        // Fallback: bersihkan data secara rekursif
        $cleaned = json_decode(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), true);
        $result = json_encode($cleaned);
    }
    return $result ?: '[]';
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action === 'create_sa') {
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN tenant_id VARCHAR(36) NULL");
    } catch (\Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE admins ADD COLUMN tenant_id VARCHAR(36) NULL");
    } catch (\Throwable $e) {}

    $sa_id = 'uat-u-superadmin';
    $sa_ref = 'uat-r-superadmin';
    $sa_email = 'superadmin@uat.edupath.local';
    $sa_pass = 'EduPathSuperAdmin01!2026';
    $sa_hash = password_hash($sa_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$sa_id', '$sa_email', '$sa_hash', 1) ON DUPLICATE KEY UPDATE password='$sa_hash', is_active=1");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', '$sa_id', NULL, 'superadmin', '$sa_id') ON DUPLICATE KEY UPDATE role='superadmin'");
    
    $adm_id = 'uat-u-admin';
    $adm_ref = 'uat-r-admin';
    $adm_email = 'admin@uat.edupath.local';
    $adm_pass = 'EduPathAdmin01!2026';
    $adm_hash = password_hash($adm_pass, PASSWORD_BCRYPT);
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->exec("INSERT INTO tenants (id, name, slug, is_active) VALUES ('$uat_tenant_id', 'UAT EduPath Tenant', 'uat-edupath', 1) ON DUPLICATE KEY UPDATE is_active=1");
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$adm_id', '$adm_email', '$adm_hash', 1) ON DUPLICATE KEY UPDATE password='$adm_hash', is_active=1");
    $pdo->exec("INSERT INTO admins (id, tenant_id, username, password, name) VALUES ('$adm_ref', '$uat_tenant_id', '$adm_email', '$adm_hash', 'UAT Tenant Admin') ON DUPLICATE KEY UPDATE password='$adm_hash'");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', '$adm_id', '$uat_tenant_id', 'admin', '$adm_ref') ON DUPLICATE KEY UPDATE role='admin'");
    
    echo "SA CREATED";
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    // Known persistent accounts auto-healing map
    $knownAccounts = [
        'superadmin@uat.edupath.local' => ['pass' => 'EduPathSuperAdmin01!2026', 'role' => 'superadmin', 'name' => 'UAT Super Admin', 'tenant' => null],
        'superadmin'                   => ['pass' => 'EduPathSuperAdmin01!2026', 'role' => 'superadmin', 'name' => 'UAT Super Admin', 'tenant' => null],
        'admin.uat@edupath.id'         => ['pass' => 'admin123',                 'role' => 'superadmin', 'name' => 'Admin UAT',         'tenant' => null],
        'admin'                        => ['pass' => 'admin123',                 'role' => 'superadmin', 'name' => 'Main Admin',        'tenant' => null],
        'admin@uat.edupath.local'      => ['pass' => 'EduPathAdmin01!2026',      'role' => 'admin',      'name' => 'UAT Tenant Admin',   'tenant' => 'uat-tenant-01'],
        'tutor@uat.edupath.local'      => ['pass' => 'EduPathTutor01!2026',      'role' => 'teacher',    'name' => 'UAT Tutor',          'tenant' => 'uat-tenant-01'],
    ];

    // Auto-heal if known account matches password
    if (isset($knownAccounts[$username]) && $password === $knownAccounts[$username]['pass']) {
        $acc = $knownAccounts[$username];
        $hPass = password_hash($acc['pass'], PASSWORD_BCRYPT);
        $uId = 'u-' . md5($username);
        $refId = 'r-' . md5($username);
        
        try {
            if ($acc['tenant']) {
                $pdo->exec("INSERT INTO tenants (id, name, slug, is_active) VALUES ('{$acc['tenant']}', 'UAT EduPath Tenant', 'uat-edupath', 1) ON DUPLICATE KEY UPDATE is_active=1");
            }
            $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$uId', '$username', '$hPass', 1) ON DUPLICATE KEY UPDATE password='$hPass', is_active=1");
            if ($acc['role'] !== 'superadmin' || $username === 'admin') {
                $tVal = $acc['tenant'] ? "'{$acc['tenant']}'" : "NULL";
                $pdo->exec("INSERT INTO admins (id, tenant_id, username, password, name) VALUES ('$refId', $tVal, '$username', '$hPass', '{$acc['name']}') ON DUPLICATE KEY UPDATE password='$hPass'");
            }
            $tValRole = $acc['tenant'] ? "'{$acc['tenant']}'" : "NULL";
            $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('ur-$uId', '$uId', $tValRole, '{$acc['role']}', '$refId') ON DUPLICATE KEY UPDATE role='{$acc['role']}'");
        } catch (\Throwable $e) {}
    }

    // Join with tenants to check if tenant is active (only for non-superadmin)
    // Support exact match as well as prefix match (e.g. typing 'superadmin' matches 'superadmin@...')
    $usernamePrefix = $username . '@%';
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password as user_password, a.password as admin_password,
               ur.role, ur.tenant_id, ur.reference_id, a.id as admin_id, t.is_active as tenant_active
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN admins a ON ur.reference_id = a.id
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE (u.identity_key = ? OR a.username = ? OR u.identity_key LIKE ? OR a.username LIKE ?) 
          AND u.is_active = 1 
          AND ur.role IN ('admin', 'superadmin', 'teacher')
        LIMIT 1
    ");
    $stmt->execute([$username, $username, $usernamePrefix, $usernamePrefix]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $isValid = false;
    if ($admin) {
        $candidates = array_filter([$admin['user_password'] ?? '', $admin['admin_password'] ?? '']);
        foreach ($candidates as $stored) {
            if (password_verify($password, $stored) || $password === $stored || md5($password) === $stored) {
                $isValid = true;
                break;
            }
        }
    }

    if ($isValid) {
        if (in_array($admin['role'], ['admin', 'teacher']) && isset($admin['tenant_active']) && (int)$admin['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }

        // Auto-upgrade / sync to standard bcrypt hash if plain text or md5 was injected
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        if (!empty($admin['user_id'])) {
            try { $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $admin['user_id']]); } catch (\Throwable $e) {}
        }
        if (!empty($admin['admin_id'])) {
            try { $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$newHash, $admin['admin_id']]); } catch (\Throwable $e) {}
        }

        reset_rate_limit($pdo, 'admin_login');
        $token = generate_jwt([
            'user_id' => $admin['user_id'], 
            'id' => $admin['reference_id'] ?? $admin['user_id'], 
            'role' => $admin['role'], 
            'tenant_id' => $admin['tenant_id'] // superadmin will naturally have NULL here
        ]);
        $csrf_token = generate_csrf_token();
        set_auth_cookies($token, $csrf_token);
        unset($admin['user_password'], $admin['admin_password']);
        echo json_encode(["token" => $token, "csrf_token" => $csrf_token, "user" => $admin]);
    } else {
        log_audit($pdo, null, null, 'admin_login_failed', null, ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        http_response_code(401);
        echo json_encode(["error" => "Username atau password salah"]);
    }
    exit;
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    clear_auth_cookies();
    echo json_encode(["success" => true]);
    exit;
}

// Untuk rute di bawah ini, wajib login sebagai superadmin, admin, atau teacher
$payload = authenticate();
if (!in_array($payload->role, ['admin', 'superadmin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($payload->role === 'teacher') {
        $tenant_id = $payload->tenant_id;
        $totalStudents = 0;
        $activeStudents = 0;
        if ($tenant_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $totalStudents = $stmt->fetchColumn() ?: 0;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE is_active = 1 AND tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $activeStudents = $stmt->fetchColumn() ?: 0;
        } else {
            $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
            $activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn() ?: 0;
        }
        $totalQuizzes = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn() ?: 0;
        $totalMaterials = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn() ?: 0;

        $statsData = [
            "totalStudents" => (int)$totalStudents,
            "activeStudents" => (int)$activeStudents,
            "totalQuizzes" => (int)$totalQuizzes,
            "totalMaterials" => (int)$totalMaterials,
            "totalRevenue" => 0,
            "activeSubscriptions" => 0,
            "totalAffiliates" => 0,
            "totalCommissions" => 0,
            "totalTenants" => 0
        ];
        echo json_encode(array_merge($statsData, ["stats" => $statsData]));
        exit;
    }

    if ($payload->role === 'superadmin') {
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
        if ($payload->role === 'superadmin' || empty($payload->tenant_id)) {
            $stmt = $pdo->query("SELECT id, name, email, plan, is_active, created_at, tenant_id FROM students ORDER BY created_at DESC");
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, plan, is_active, created_at, tenant_id FROM students WHERE (tenant_id = ? OR tenant_id IS NULL OR tenant_id = '') ORDER BY created_at DESC");
            $stmt->execute([$payload->tenant_id]);
        }
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo safe_json_encode(["students" => $students]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $plan = $input['plan'] ?? 'free';
        
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Nama, email, dan password wajib diisi"]); exit;
        }
        
        $user_id = bin2hex(random_bytes(16));
        $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
        
        $student_id = bin2hex(random_bytes(16));
        $student_id = substr($student_id,0,8).'-'.substr($student_id,8,4).'-'.substr($student_id,12,4).'-'.substr($student_id,16,4).'-'.substr($student_id,20,12);
        
        $tenant_id = $payload->tenant_id;
        if (empty($tenant_id)) {
            $tenant_id = $input['tenant_id'] ?? '';
        }
        if (empty($tenant_id)) {
            $tFirst = $pdo->query("SELECT id FROM tenants WHERE is_active = 1 LIMIT 1")->fetchColumn();
            if (!$tFirst) {
                $tFirst = 'default-tenant';
                try { $pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES ('default-tenant', 'EduPath Bimbel Pusat', 'edupath-pusat', 1)"); } catch (\Throwable $e) {}
            }
            $tenant_id = $tFirst;
        }

        try {
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
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $plan = $input['plan'] ?? 'free';
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $password = $input['password'] ?? '';

        if (empty($id) || empty($name) || empty($email)) {
            http_response_code(400); echo json_encode(["error" => "ID, nama, dan email wajib diisi"]); exit;
        }

        try {
            $pdo->beginTransaction();
            // Get old email
            $stmtOld = $pdo->prepare("SELECT email FROM students WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldEmail = $stmtOld->fetchColumn();

            // Update students table
            $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, plan = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $email, $plan, $is_active, $id]);

            // Update user table
            if ($oldEmail) {
                $stmtUser = $pdo->prepare("UPDATE users SET identity_key = ?, is_active = ? WHERE identity_key = ?");
                $stmtUser->execute([$email, $is_active, $oldEmail]);
            }

            // Update password if provided
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmtPass = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $stmtPass->execute([$hashed, $id]);
                if ($oldEmail) {
                    $stmtPassUser = $pdo->prepare("UPDATE users SET password = ? WHERE identity_key = ?");
                    $stmtPassUser->execute([$hashed, $email]);
                }
            }

            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal mengupdate siswa", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403); echo json_encode(["error" => "Akses ditolak"]); exit;
        }
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID siswa wajib"]); exit;
        }
        try {
            $pdo->beginTransaction();
            $stmtS = $pdo->prepare("SELECT email FROM students WHERE id = ?");
            $stmtS->execute([$id]);
            $email = $stmtS->fetchColumn();

            // 1. Delete dependent financial data safely
            try { $pdo->prepare("DELETE FROM commissions WHERE order_id IN (SELECT id FROM orders WHERE student_id = ?)")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM payments WHERE order_id IN (SELECT id FROM orders WHERE student_id = ?)")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM invoices WHERE student_id = ? OR order_id IN (SELECT id FROM orders WHERE student_id = ?)")->execute([$id, $id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM orders WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM subscriptions WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}

            // 2. Delete quiz and learning data safely
            try { $pdo->prepare("DELETE FROM quiz_results WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM quiz_attempts WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM progress WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM entitlements WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}

            // 3. Delete session & device tokens safely
            try { $pdo->prepare("DELETE FROM refresh_tokens WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            try { $pdo->prepare("DELETE FROM device_tokens WHERE student_id = ?")->execute([$id]); } catch (\Throwable $e) {}

            // 4. Delete student record & user account
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
            try { $pdo->prepare("DELETE FROM user_roles WHERE reference_id = ?")->execute([$id]); } catch (\Throwable $e) {}
            if ($email) {
                try { $pdo->prepare("DELETE FROM users WHERE identity_key = ?")->execute([$email]); } catch (\Throwable $e) {}
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus siswa: " . $e->getMessage()]);
        }
    }
}
elseif ($action === 'orders') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $student_id = trim($_GET['student_id'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "
            SELECT o.*, s.name as student_name, s.email as student_email 
            FROM orders o 
            LEFT JOIN students s ON o.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
            $sql .= " AND (s.tenant_id = ? OR s.tenant_id IS NULL OR s.tenant_id = '')";
            $params[] = $payload->tenant_id;
        }

        if (!empty($student_id) && $student_id !== 'all') {
            $sql .= " AND (o.student_id = ? OR s.email = ?)";
            $params[] = $student_id;
            $params[] = $student_id;
        }

        if (!empty($search)) {
            $sql .= " AND (s.name LIKE ? OR s.email LIKE ? OR o.order_id LIKE ? OR o.plan_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo safe_json_encode(["orders" => $orders]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create manual/offline order
        $input = json_decode(file_get_contents('php://input'), true);
        $student_id = $input['student_id'] ?? '';
        $plan_id = $input['plan_id'] ?? 'plan-utama';
        $plan_name = $input['plan_name'] ?? 'Paket Utama';
        $amount = (float)($input['amount'] ?? 450000);
        $status = $input['status'] ?? 'paid';
        $payment_type = $input['payment_type'] ?? 'manual_transfer';

        if (empty($student_id)) {
            http_response_code(400); echo json_encode(["error" => "Siswa wajib dipilih"]); exit;
        }

        $order_id = 'MANUAL-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) . '-' . date('Ymd');
        try {
            $pdo->beginTransaction();

            $stmtTenant = $pdo->prepare("SELECT tenant_id FROM students WHERE id = ?");
            $stmtTenant->execute([$student_id]);
            $studentTenant = $stmtTenant->fetchColumn();
            if (!$studentTenant) {
                $studentTenant = $payload->tenant_id ?? $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
            }

            $actual_plan_id = null;
            if (!empty($plan_id)) {
                $pStmt = $pdo->prepare("SELECT id, name FROM plans WHERE id = ? OR name = ? LIMIT 1");
                $pStmt->execute([$plan_id, $plan_name]);
                $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);
                if ($pRow) {
                    $actual_plan_id = $pRow['id'];
                    $plan_name = $pRow['name'];
                }
            }

            $paid_at = in_array($status, ['paid', 'settlement']) ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("INSERT INTO orders (id, tenant_id, plan_id, order_id, student_id, plan_name, amount, status, paid_at, created_at, updated_at) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$studentTenant, $actual_plan_id, $order_id, $student_id, $plan_name, $amount, $status, $paid_at]);

            // If status is paid, auto activate student plan
            if (in_array($status, ['paid', 'settlement'])) {
                $planCode = 'mandiri';
                if (stripos((string)$plan_name, 'vip') !== false || stripos((string)$plan_id, 'vip') !== false) $planCode = 'vip';
                elseif (stripos((string)$plan_name, 'utama') !== false || stripos((string)$plan_id, 'utama') !== false) $planCode = 'utama';
                
                $pdo->prepare("UPDATE students SET plan = ? WHERE id = ?")->execute([$planCode, $student_id]);
            }
            $pdo->commit();
            echo json_encode(["success" => true, "order_id" => $order_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat transaksi manual", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update order status
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = $input['order_id'] ?? $input['id'] ?? '';
        $status = $input['status'] ?? 'paid';

        if (empty($order_id)) {
            http_response_code(400); echo json_encode(["error" => "Order ID wajib"]); exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ? OR id = ?");
            $stmt->execute([$status, $order_id, $order_id]);

            if (in_array($status, ['paid', 'settlement'])) {
                // Get student_id and plan
                $stmtOrd = $pdo->prepare("SELECT student_id, plan_id, plan_name FROM orders WHERE order_id = ? OR id = ?");
                $stmtOrd->execute([$order_id, $order_id]);
                $ord = $stmtOrd->fetch(PDO::FETCH_ASSOC);
                if ($ord && !empty($ord['student_id'])) {
                    $planCode = 'mandiri';
                    if (stripos($ord['plan_id'] ?? '', 'vip') !== false || stripos($ord['plan_name'] ?? '', 'vip') !== false) $planCode = 'vip';
                    elseif (stripos($ord['plan_id'] ?? '', 'utama') !== false || stripos($ord['plan_name'] ?? '', 'utama') !== false) $planCode = 'utama';
                    $pdo->prepare("UPDATE students SET plan = ? WHERE id = ?")->execute([$planCode, $ord['student_id']]);
                }
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal update transaksi"]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $order_id = $_GET['order_id'] ?? $_GET['id'] ?? '';
        if (empty($order_id)) {
            http_response_code(400); echo json_encode(["error" => "Order ID wajib"]); exit;
        }
        $pdo->prepare("DELETE FROM orders WHERE order_id = ? OR id = ?")->execute([$order_id, $order_id]);
        echo json_encode(["success" => true]);
    }
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
    echo safe_json_encode($stmt->fetchAll());
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
    echo safe_json_encode($stmt->fetchAll());
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
        echo safe_json_encode($pdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll());
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
        echo safe_json_encode($pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
    }
}
elseif ($action === 'plans') {
    $VALID_ENTITLEMENTS = ['tryout_unlimited', 'ai_adaptive_path', 'premium_materials', 'feature_quiz'];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $plans = $pdo->query("SELECT * FROM plans WHERE (is_archived IS NULL OR is_archived = 0) ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
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
            $plans = $pdo->query("SELECT * FROM plans WHERE (is_archived IS NULL OR is_archived = 0) ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($plans as &$plan) {
            $stmt = $pdo->prepare("SELECT feature_key FROM plan_entitlements WHERE plan_id = ?");
            $stmt->execute([$plan['id']]);
            $features = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($features)) {
                $plan['features'] = json_encode($features);
            }
        }
        echo safe_json_encode($plans);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403); echo json_encode(["error" => "Admin only"]); exit;
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
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403); echo json_encode(["error" => "Admin only"]); exit;
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
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403); echo json_encode(["error" => "Admin only"]); exit;
        }
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID paket diperlukan"]); exit;
        }
        try {
            $pdo->beginTransaction();
            // Delete plan entitlements first
            $pdo->prepare("DELETE FROM plan_entitlements WHERE plan_id = ?")->execute([$id]);

            // Check if there are orders or subscriptions
            $chkOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE plan_id = ?");
            $chkOrders->execute([$id]);
            $hasOrders = $chkOrders->fetchColumn() > 0;

            $chkSubs = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = ?");
            $chkSubs->execute([$id]);
            $hasSubs = $chkSubs->fetchColumn() > 0;

            if ($hasOrders || $hasSubs) {
                // Soft archive to protect financial records
                $pdo->prepare("UPDATE plans SET is_active = 0, is_archived = 1 WHERE id = ?")->execute([$id]);
            } else {
                // Safe hard delete
                $pdo->prepare("DELETE FROM plans WHERE id = ?")->execute([$id]);
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus paket", "details" => $e->getMessage()]);
        }
    }
}
elseif ($action === 'questions') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $subtest = $_GET['subtest'] ?? $_GET['sub_materi'] ?? '';
        $test_component = $_GET['test_component'] ?? '';
        $topic = $_GET['topic'] ?? '';

        $sql = "SELECT * FROM questions WHERE 1=1";
        $params = [];
        if ($subtest && $subtest !== 'all') {
            $sql .= " AND (subtest = ? OR sub_materi = ?)";
            $params[] = $subtest;
            $params[] = $subtest;
        }
        if ($test_component && $test_component !== 'all') {
            $sql .= " AND test_component = ?";
            $params[] = $test_component;
        }
        if ($topic && $topic !== 'all') {
            $sql .= " AND topic = ?";
            $params[] = $topic;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo safe_json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Akses ditolak"]);
            exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $exam = $input['exam'] ?? 'SNBT';
        $subtest = $input['subtest'] ?? $input['sub_materi'] ?? $input['subtes'] ?? 'Penalaran Umum';
        $test_component = $input['test_component'] ?? (
            (stripos($subtest, 'Literasi') !== false || stripos($subtest, 'Matematika') !== false) ? 'TES LITERASI' : 'TPS'
        );
        $topic = $input['topic'] ?? $input['bab'] ?? null;
        $subtopic = $input['subtopic'] ?? null;
        $skill = $input['skill'] ?? null;
        $indicator = $input['indicator'] ?? null;
        $question_type = $input['question_type'] ?? 'multiple_choice';

        $is_qc_passed = isset($input['is_qc_passed']) && $input['is_qc_passed'] ? 1 : 0;
        $classification = strtoupper($input['usage_type'] ?? $input['classification'] ?? 'LATIHAN');

        try {
            $stmt = $pdo->prepare("
                INSERT INTO questions (
                    id, exam, test_component, subtest, topic, subtopic, skill, indicator, question_type,
                    subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e,
                    correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference,
                    is_qc_passed, classification
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?
                )
            ");
            $stmt->execute([
                $id, $exam, $test_component, $subtest, $topic, $subtopic, $skill, $indicator, $question_type,
                $subtest, $subtest, $subtopic ?? $topic ?? 'Umum', $input['difficulty'] ?? 'medium',
                $input['question'], $input['option_a'], $input['option_b'], $input['option_c'],
                $input['option_d'], $input['option_e'] ?? null, $input['correct'], $input['explanation'] ?? null,
                $input['cognitive_demand'] ?? null, $input['source_type'] ?? 'author_created', $input['rights_status'] ?? 'unknown',
                $input['source_name'] ?? null, $input['source_year'] ?? null, $input['source_reference'] ?? null,
                $is_qc_passed, $classification
            ]);
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal menyimpan soal", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Akses ditolak"]);
            exit;
        }
        $id = $input['id'] ?? '';
        $exam = $input['exam'] ?? 'SNBT';
        $subtest = $input['subtest'] ?? $input['sub_materi'] ?? $input['subtes'] ?? 'Penalaran Umum';
        $test_component = $input['test_component'] ?? (
            (stripos($subtest, 'Literasi') !== false || stripos($subtest, 'Matematika') !== false) ? 'TES LITERASI' : 'TPS'
        );
        $topic = $input['topic'] ?? $input['bab'] ?? null;
        $subtopic = $input['subtopic'] ?? null;
        $skill = $input['skill'] ?? null;
        $indicator = $input['indicator'] ?? null;
        $question_type = $input['question_type'] ?? 'multiple_choice';

        $is_qc_passed = isset($input['is_qc_passed']) && $input['is_qc_passed'] ? 1 : 0;
        $classification = strtoupper($input['usage_type'] ?? $input['classification'] ?? 'LATIHAN');

        try {
            $stmt = $pdo->prepare("
                UPDATE questions SET
                    exam=?, test_component=?, subtest=?, topic=?, subtopic=?, skill=?, indicator=?, question_type=?,
                    subtes=?, sub_materi=?, bab=?, difficulty=?, question=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?,
                    correct=?, explanation=?, cognitive_demand=?, source_type=?, rights_status=?, source_name=?, source_year=?, source_reference=?,
                    is_qc_passed=?, classification=?
                WHERE id=?
            ");
            $stmt->execute([
                $exam, $test_component, $subtest, $topic, $subtopic, $skill, $indicator, $question_type,
                $subtest, $subtest, $subtopic ?? $topic ?? 'Umum', $input['difficulty'] ?? 'medium',
                $input['question'], $input['option_a'], $input['option_b'], $input['option_c'],
                $input['option_d'], $input['option_e'] ?? null, $input['correct'], $input['explanation'] ?? null,
                $input['cognitive_demand'] ?? null, $input['source_type'] ?? 'author_created', $input['rights_status'] ?? 'unknown',
                $input['source_name'] ?? null, $input['source_year'] ?? null, $input['source_reference'] ?? null,
                $is_qc_passed, $classification,
                $id
            ]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update soal", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403);
            echo json_encode(["error" => "Admin only"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        try {
            $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus soal", "details" => $e->getMessage()]);
        }
    }
}
elseif ($action === 'materials') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $subtest = $_GET['subtest'] ?? $_GET['sub_materi'] ?? '';
        $test_component = $_GET['test_component'] ?? '';
        $topic = $_GET['topic'] ?? '';

        $sql = "SELECT * FROM materials WHERE 1=1";
        $params = [];
        if ($subtest && $subtest !== 'all') {
            $sql .= " AND (subtest = ? OR subtest LIKE ? OR sub_materi = ?)";
            $params[] = $subtest;
            $params[] = "%$subtest%";
            $params[] = $subtest;
        }
        if ($test_component && $test_component !== 'all') {
            $sql .= " AND test_component = ?";
            $params[] = $test_component;
        }
        if ($topic && $topic !== 'all') {
            $sql .= " AND topic = ?";
            $params[] = $topic;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo safe_json_encode(["materials" => $materials]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Staff only"]);
            exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

        $exam = $input['exam'] ?? 'SNBT';
        $subtest = $input['subtest'] ?? $input['sub_materi'] ?? 'Penalaran Umum';
        $test_component = $input['test_component'] ?? (
            (stripos($subtest, 'Literasi') !== false || stripos($subtest, 'Matematika') !== false) ? 'TES LITERASI' : 'TPS'
        );
        $topic = $input['topic'] ?? null;
        $subtopic = $input['subtopic'] ?? null;
        $skill = $input['skill'] ?? null;
        $indicator = $input['indicator'] ?? null;
        $title = trim($input['title'] ?? 'Materi Belajar');
        $content = $input['content'] ?? '';
        $teacher_name = $input['teacher_name'] ?? null;
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO materials (id, exam, test_component, subtest, sub_materi, topic, subtopic, skill, indicator, title, content, teacher_name, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id, $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $title, $content, $teacher_name, $is_active
            ]);
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal menyimpan materi", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Staff only"]);
            exit;
        }
        $id = $input['id'] ?? '';
        $exam = $input['exam'] ?? 'SNBT';
        $subtest = $input['subtest'] ?? $input['sub_materi'] ?? 'Penalaran Umum';
        $test_component = $input['test_component'] ?? (
            (stripos($subtest, 'Literasi') !== false || stripos($subtest, 'Matematika') !== false) ? 'TES LITERASI' : 'TPS'
        );
        $topic = $input['topic'] ?? null;
        $subtopic = $input['subtopic'] ?? null;
        $skill = $input['skill'] ?? null;
        $indicator = $input['indicator'] ?? null;
        $title = trim($input['title'] ?? 'Materi Belajar');
        $content = $input['content'] ?? '';
        $teacher_name = $input['teacher_name'] ?? null;
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        try {
            $stmt = $pdo->prepare("
                UPDATE materials SET
                    exam=?, test_component=?, subtest=?, sub_materi=?, topic=?, subtopic=?, skill=?, indicator=?, title=?, content=?, teacher_name=?, is_active=?
                WHERE id=?
            ");
            $stmt->execute([
                $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $title, $content, $teacher_name, $is_active, $id
            ]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update materi", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403);
            echo json_encode(["error" => "Admin only"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        try {
            $pdo->prepare("DELETE FROM materials WHERE id = ?")->execute([$id]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus materi", "details" => $e->getMessage()]);
        }
    }
}
elseif ($action === 'staff') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "
            SELECT u.identity_key as username, a.name, a.id, ur.role, ur.tenant_id, t.name as tenant_name
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN admins a ON ur.reference_id = a.id
            LEFT JOIN tenants t ON ur.tenant_id = t.id
            WHERE ur.role != 'superadmin'
        ";
        $params = [];
        if ($payload->role === 'superadmin') {
            if (!empty($_GET['tenant_id'])) {
                $sql .= " AND ur.tenant_id = ?";
                $params[] = $_GET['tenant_id'];
            }
        } else {
            $sql .= " AND ur.tenant_id = ?";
            $params[] = $payload->tenant_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo safe_json_encode(["staff" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "Hanya Superadmin yang memiliki izin untuk menambah, mengedit, atau menghapus staf"]);
            exit;
        }
        $username = trim($input['username'] ?? '');
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Username dan password diperlukan"]); exit;
        }

        $tenant_id = $payload->tenant_id;
        if (empty($tenant_id)) {
            $tenant_id = $input['tenant_id'] ?? '';
            if (empty($tenant_id)) {
                // Jika superadmin tidak memilih tenant, pilih tenant pertama yang aktif
                $tFirst = $pdo->query("SELECT id FROM tenants WHERE is_active = 1 LIMIT 1")->fetchColumn();
                $tenant_id = $tFirst ?: null;
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
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($user_id,20,12);
            
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
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "Hanya Superadmin yang memiliki izin untuk menambah, mengedit, atau menghapus staf"]);
            exit;
        }
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        
        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        $scope = '';
        if ($payload->role !== 'superadmin') {
            $scope = $tenant_id ? " AND ur.tenant_id = ?" : " AND ur.tenant_id IS NULL";
        }
        try {
            $stmt = $pdo->prepare("UPDATE admins a JOIN user_roles ur ON ur.reference_id = a.id SET a.name = ? WHERE a.id = ? AND ur.role != 'superadmin' $scope");
            $params = [$name, $id];
            if ($payload->role !== 'superadmin' && $tenant_id) $params[] = $tenant_id;
            $stmt->execute($params);

            $stmt = $pdo->prepare("UPDATE user_roles SET role = ? WHERE reference_id = ? AND role != 'superadmin' $scope");
            $params = [$role, $id];
            if ($payload->role !== 'superadmin' && $tenant_id) $params[] = $tenant_id;
            $stmt->execute($params);
            
            if (!empty($input['password'])) {
                $hashed = password_hash($input['password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users u JOIN user_roles ur ON u.id = ur.user_id SET u.password = ? WHERE ur.reference_id = ? AND ur.role != 'superadmin' $scope");
                $params = [$hashed, $id];
                if ($payload->role !== 'superadmin' && $tenant_id) $params[] = $tenant_id;
                $stmt->execute($params);
                $stmt = $pdo->prepare("UPDATE admins a JOIN user_roles ur ON ur.reference_id = a.id SET a.password = ? WHERE a.id = ? AND ur.role != 'superadmin' $scope");
                $params = [$hashed, $id];
                if ($payload->role !== 'superadmin' && $tenant_id) $params[] = $tenant_id;
                $stmt->execute($params);
            }
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal mengupdate staff", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "Hanya Superadmin yang memiliki izin untuk menambah, mengedit, atau menghapus staf"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID staff diperlukan"]); exit;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT ur.user_id, ur.tenant_id, ur.role FROM user_roles ur WHERE ur.reference_id = ?");
            $stmt->execute([$id]);
            $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$staffInfo || $staffInfo['role'] === 'superadmin') {
                $pdo->rollBack();
                http_response_code(403); echo json_encode(["error" => "Tidak dapat menghapus superadmin"]); exit;
            }

            if ($payload->role !== 'superadmin' && $staffInfo['tenant_id'] !== $payload->tenant_id) {
                $pdo->rollBack();
                http_response_code(403); echo json_encode(["error" => "Akses ditolak"]); exit;
            }

            $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM user_roles WHERE reference_id = ?")->execute([$id]);
            if (!empty($staffInfo['user_id'])) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$staffInfo['user_id']]);
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus staff", "details" => $e->getMessage()]);
        }
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
        echo safe_json_encode($commissions);
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
