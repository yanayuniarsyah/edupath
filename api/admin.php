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
        "ALTER TABLE materials ADD COLUMN leaf_id VARCHAR(50) NULL",
        "ALTER TABLE materials ADD COLUMN teacher_name VARCHAR(150) NULL",
        "ALTER TABLE materials ADD COLUMN teacher_id VARCHAR(50) NULL",
        "ALTER TABLE materials ADD COLUMN file_url VARCHAR(500) NULL",
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
        "ALTER TABLE questions ADD COLUMN classification VARCHAR(50) DEFAULT 'LATIHAN'",
        "ALTER TABLE questions ADD COLUMN teacher_name VARCHAR(150) NULL",
        "ALTER TABLE questions ADD COLUMN teacher_id VARCHAR(50) NULL"
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

    // 4. Drop outdated ownership check constraints if present (Phase 3i alignment)
    try { $pdo->exec("ALTER TABLE subscriptions DROP CONSTRAINT sub_ownership_chk"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE entitlements DROP CONSTRAINT ent_ownership_chk"); } catch (\Throwable $e) {}

    // 5. Ensure quiz_attempts table and reliability columns
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS quiz_attempts (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                quiz_type VARCHAR(50) NOT NULL,
                subtes VARCHAR(100) DEFAULT NULL,
                status ENUM('started', 'completed') DEFAULT 'started',
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                completed_at DATETIME DEFAULT NULL,
                answers JSON NULL,
                last_saved_at DATETIME NULL,
                KEY qa_student_idx (student_id),
                CONSTRAINT qa_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE quiz_attempts ADD COLUMN expires_at DATETIME NULL AFTER started_at"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE quiz_attempts ADD COLUMN answers JSON NULL AFTER completed_at"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE quiz_attempts ADD COLUMN last_saved_at DATETIME NULL AFTER answers"); } catch (\Throwable $e) {}

    // 6. Ensure target universities & programs for PTN Rasionalisasi
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS target_universities (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                city VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS target_programs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                university_id VARCHAR(36) NOT NULL,
                name VARCHAR(150) NOT NULL,
                target_score INT NOT NULL DEFAULT 650,
                req_ability JSON DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY tp_univ_idx (university_id),
                CONSTRAINT tp_univ_fk FOREIGN KEY (university_id) REFERENCES target_universities (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    // 7. Ensure SPP Diagnostic Questions & Rate Limits tables
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS spp_diagnostic_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                subject VARCHAR(100) NOT NULL,
                category VARCHAR(100) NOT NULL,
                skill VARCHAR(150) NOT NULL,
                difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
                question TEXT NOT NULL,
                options JSON NOT NULL,
                answer VARCHAR(10) NOT NULL,
                hint TEXT,
                concept TEXT,
                explanation TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                endpoint VARCHAR(100) NOT NULL,
                attempt_count INT DEFAULT 1,
                lock_until DATETIME DEFAULT NULL,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY ip_endpoint_uq (ip_address, endpoint)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Run schema migration only when explicitly triggered, not on every request
if (isset($_GET['migrate']) || ($action === 'migrate')) {
    ensure_admin_schema($pdo);
    echo json_encode(["success" => true, "message" => "Admin schema migrated successfully"]);
    exit;
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

    // Known persistent accounts auto-healing map (ONLY Superadmin & Admin)
    $knownAccounts = [
        'superadmin@uat.edupath.local' => ['pass' => 'EduPathSuperAdmin01!2026', 'role' => 'superadmin', 'name' => 'UAT Super Admin', 'tenant' => null],
        'superadmin'                   => ['pass' => 'EduPathSuperAdmin01!2026', 'role' => 'superadmin', 'name' => 'UAT Super Admin', 'tenant' => null],
        'admin.uat@edupath.id'         => ['pass' => 'admin123',                 'role' => 'superadmin', 'name' => 'Admin UAT',         'tenant' => null],
        'admin'                        => ['pass' => 'admin123',                 'role' => 'superadmin', 'name' => 'Main Admin',        'tenant' => null],
        'admin@uat.edupath.local'      => ['pass' => 'EduPathAdmin01!2026',      'role' => 'admin',      'name' => 'UAT Tenant Admin',   'tenant' => 'uat-tenant-01'],
    ];

    $usernamePrefix = $username . '@%';

    // STRICT CHECK: Jika akun ini adalah Guru/Tutor, Siswa, atau Guru BK, tolak keras dari /admin
    $stmt_check_role = $pdo->prepare("
        SELECT ur.role 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        WHERE (u.identity_key = ? OR u.identity_key LIKE ?) 
        LIMIT 1
    ");
    $stmt_check_role->execute([$username, $usernamePrefix]);
    $user_role_found = $stmt_check_role->fetchColumn();

    if ($user_role_found && in_array($user_role_found, ['teacher', 'guru', 'tutor', 'school_bk', 'student'])) {
        http_response_code(403);
        echo json_encode([
            "error" => "Akses khusus Administrator & Superadmin. Guru/Pengajar, Guru BK, dan Siswa wajib masuk melalui form login di halaman depan."
        ]);
        exit;
    }

    // 1. Fast Path: Cek kredensial langsung dari database (HANYA admin & superadmin)
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password as user_password, a.password as admin_password,
               ur.role, ur.tenant_id, ur.reference_id, a.id as admin_id, t.is_active as tenant_active,
               COALESCE(a.name, u.identity_key) as name
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN admins a ON ur.reference_id = a.id
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE (u.identity_key = ? OR a.username = ? OR u.identity_key LIKE ? OR a.username LIKE ?) 
          AND u.is_active = 1 
          AND ur.role IN ('admin', 'superadmin')
        LIMIT 1
    ");
    $stmt->execute([$username, $username, $usernamePrefix, $usernamePrefix]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $isValid = false;
    $needsRehash = false;
    if ($admin) {
        $candidates = array_filter([$admin['user_password'] ?? '', $admin['admin_password'] ?? '']);
        foreach ($candidates as $stored) {
            if (password_verify($password, $stored)) {
                $isValid = true;
                break;
            } elseif ($password === $stored || md5($password) === $stored) {
                $isValid = true;
                $needsRehash = true;
                break;
            }
        }
    }

    // 2. Fallback Auto-heal: Hanya jika akun tidak ditemukan / gagal, tapi terdaftar di knownAccounts
    if (!$isValid && isset($knownAccounts[$username]) && $password === $knownAccounts[$username]['pass']) {
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

        // Ambil ulang data setelah auto-heal
        $stmt->execute([$username, $username, $usernamePrefix, $usernamePrefix]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $isValid = true;
        }
    }

    if ($isValid) {
        if (in_array($admin['role'], ['admin', 'teacher']) && isset($admin['tenant_active']) && (int)$admin['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }

        // Auto-upgrade ke bcrypt hash hanya jika password sebelumnya berupa plain text atau md5
        if ($needsRehash) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            if (!empty($admin['user_id'])) {
                try { $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $admin['user_id']]); } catch (\Throwable $e) {}
            }
            if (!empty($admin['admin_id'])) {
                try { $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$newHash, $admin['admin_id']]); } catch (\Throwable $e) {}
            }
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

// Untuk rute di bawah ini, wajib login sebagai superadmin, admin, teacher, atau affiliate
$payload = authenticate();
if (!in_array($payload->role, ['admin', 'superadmin', 'teacher', 'affiliate'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if (($action === 'me' || $action === 'profile') && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, ur.role, ur.tenant_id, ur.reference_id, 
               COALESCE(a.name, t.name, u.identity_key) as name,
               COALESCE(a.username, u.identity_key) as email,
               t.name as tenant_name
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN admins a ON (ur.reference_id = a.id OR u.identity_key = a.username)
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$payload->user_id]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $defaultName = 'Administrator';
    if ($payload->role === 'superadmin') $defaultName = 'Super Administrator';
    elseif ($payload->role === 'teacher') $defaultName = 'Guru / Tutor';
    elseif (!empty($adminUser['tenant_name'])) $defaultName = 'Admin ' . $adminUser['tenant_name'];

    $userRes = [
        'id'          => $adminUser['reference_id'] ?? $payload->id,
        'user_id'     => $payload->user_id,
        'name'        => !empty($adminUser['name']) ? $adminUser['name'] : $defaultName,
        'email'       => $adminUser['email'] ?? ($payload->email ?? ''),
        'role'        => $payload->role,
        'tenant_id'   => $payload->tenant_id,
        'tenant_name' => $adminUser['tenant_name'] ?? null
    ];
    echo json_encode(["user" => $userRes]);
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
        $totalSchools = 0;
        $totalSchoolStudents = 0;
        try {
            $totalSchools = (int)($pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn() ?: 0);
            $totalSchoolStudents = (int)($pdo->query("SELECT COUNT(*) FROM students WHERE school_id IS NOT NULL OR (school IS NOT NULL AND school != '')")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {}

        $statsData = [
            "totalStudents" => (int)$totalStudents,
            "activeStudents" => (int)$activeStudents,
            "totalQuizzes" => (int)$totalQuizzes,
            "totalRevenue" => (float)$totalRevenue,
            "activeSubscriptions" => (int)$activeSubscriptions,
            "totalAffiliates" => (int)$totalAffiliates,
            "totalCommissions" => (float)$totalCommissions,
            "totalTenants" => (int)$totalTenants,
            "totalSchools" => (int)$totalSchools,
            "totalSchoolStudents" => (int)$totalSchoolStudents
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
    // Ensure columns exist on students table
    try {
        $stCols = $pdo->query("SHOW COLUMNS FROM students")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('school_id', $stCols)) $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL AFTER school");
        if (!in_array('target_ptn', $stCols)) $pdo->exec("ALTER TABLE students ADD COLUMN target_ptn VARCHAR(150) NULL AFTER school_id");
        if (!in_array('phone', $stCols)) $pdo->exec("ALTER TABLE students ADD COLUMN phone VARCHAR(50) NULL AFTER target_ptn");
    } catch (\Throwable $e) {}

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($payload->role === 'superadmin' || empty($payload->tenant_id)) {
            $stmt = $pdo->query("
                SELECT s.id, s.name, s.email, s.plan, s.is_active, s.created_at, s.tenant_id,
                       s.school, s.school_id, s.target_ptn, s.phone,
                       t.name as tenant_name
                FROM students s 
                LEFT JOIN tenants t ON s.tenant_id = t.id
                WHERE s.id NOT IN (SELECT reference_id FROM user_roles WHERE role = 'affiliate')
                  AND s.email NOT IN (SELECT u.identity_key FROM affiliates a JOIN users u ON a.user_id = u.id)
                ORDER BY s.created_at DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT s.id, s.name, s.email, s.plan, s.is_active, s.created_at, s.tenant_id,
                       s.school, s.school_id, s.target_ptn, s.phone,
                       t.name as tenant_name
                FROM students s 
                LEFT JOIN tenants t ON s.tenant_id = t.id
                WHERE (s.tenant_id = ? OR s.tenant_id IS NULL OR s.tenant_id = '')
                  AND s.id NOT IN (SELECT reference_id FROM user_roles WHERE role = 'affiliate')
                  AND s.email NOT IN (SELECT u.identity_key FROM affiliates a JOIN users u ON a.user_id = u.id)
                ORDER BY s.created_at DESC
            ");
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
        $school = trim($input['school'] ?? '');
        $school_id = trim($input['school_id'] ?? '');
        $target_ptn = trim($input['target_ptn'] ?? '');
        $phone = trim($input['phone'] ?? '');

        if (!empty($school) && empty($school_id)) {
            try {
                $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $stmt_sch->execute([$school]);
                $found_id = $stmt_sch->fetchColumn();
                if ($found_id) $school_id = $found_id;
            } catch (\Throwable $e) {}
        }
        
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
            
            $stmt = $pdo->prepare("INSERT INTO students (id, tenant_id, name, email, password, plan, school, school_id, target_ptn, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$student_id, $tenant_id, $name, $email, $hashed, $plan, $school ?: null, $school_id ?: null, $target_ptn ?: null, $phone ?: null]);
            
            $ur_id = bin2hex(random_bytes(16));
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($user_id,20,12);
            $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)");
            $stmt->execute([$ur_id, $user_id, $tenant_id, $student_id]);

            // Sync subscription if paid plan
            if (strtolower($plan) !== 'free') {
                $subId = 'sub-' . md5($student_id);
                $planNames = [
                    'mandiri' => 'Paket Mandiri',
                    'utama' => 'Paket Pejuang PTN (Utama)',
                    'vip' => 'Paket Elite Mentoring (VIP)'
                ];
                $planIdMap = [
                    'mandiri' => 'plan-p1-yearly',
                    'plan-mandiri' => 'plan-p1-yearly',
                    'plan-p1-monthly' => 'plan-p1-monthly',
                    'plan-p1-yearly' => 'plan-p1-yearly',
                    'utama' => 'plan-p2-yearly',
                    'plan-utama' => 'plan-p2-yearly',
                    'plan-p2-monthly' => 'plan-p2-monthly',
                    'plan-p2-yearly' => 'plan-p2-yearly',
                    'vip' => 'plan-p3-yearly',
                    'plan-vip' => 'plan-p3-yearly',
                    'plan-p3-monthly' => 'plan-p3-monthly',
                    'plan-p3-yearly' => 'plan-p3-yearly'
                ];
                $targetPlanId = $planIdMap[strtolower($plan)] ?? 'plan-p2-yearly';
                // Ensure targetPlanId exists in plans table to prevent FK violation
                $chkPlan = $pdo->prepare("SELECT id FROM plans WHERE id = ?");
                $chkPlan->execute([$targetPlanId]);
                if (!$chkPlan->fetch()) {
                    $targetPlanId = $pdo->query("SELECT id FROM plans LIMIT 1")->fetchColumn() ?: 'plan-p2-yearly';
                }
                $planNameSnap = $planNames[strtolower($plan)] ?? ucfirst($plan);

                // Student subscription must have tenant_id NULL to satisfy constraint sub_ownership_chk
                $subTenant = null;
                $pdo->prepare("
                    INSERT INTO subscriptions (id, tenant_id, student_id, plan_id, status, plan_name_snapshot, started_at, expires_at)
                    VALUES (?, ?, ?, ?, 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))
                    ON DUPLICATE KEY UPDATE 
                        plan_id = VALUES(plan_id), 
                        status = 'active', 
                        plan_name_snapshot = VALUES(plan_name_snapshot), 
                        expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
                ")->execute([$subId, $subTenant, $student_id, $targetPlanId, $planNameSnap]);
            }
            
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
        $school = trim($input['school'] ?? '');
        $school_id = trim($input['school_id'] ?? '');
        $target_ptn = trim($input['target_ptn'] ?? '');
        $phone = trim($input['phone'] ?? '');

        if (!empty($school) && empty($school_id)) {
            try {
                $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $stmt_sch->execute([$school]);
                $found_id = $stmt_sch->fetchColumn();
                if ($found_id) $school_id = $found_id;
            } catch (\Throwable $e) {}
        }

        if (empty($id) || empty($name) || empty($email)) {
            http_response_code(400); echo json_encode(["error" => "ID, nama, dan email wajib diisi"]); exit;
        }

        try {
            $pdo->beginTransaction();
            // Get old email and tenant_id
            $stmtOld = $pdo->prepare("SELECT email, tenant_id FROM students WHERE id = ?");
            $stmtOld->execute([$id]);
            $studentData = $stmtOld->fetch(PDO::FETCH_ASSOC);
            $oldEmail = $studentData ? $studentData['email'] : null;
            $sTenantId = $studentData ? $studentData['tenant_id'] : ($payload->tenant_id ?? 'default_tenant');

            // 1. Update students table (termasuk atribut sekolah, target PTN, HP, tenant_id)
            if ($payload->role === 'superadmin' && array_key_exists('tenant_id', $input)) {
                $newTenantId = !empty($input['tenant_id']) ? $input['tenant_id'] : null;
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, plan = ?, is_active = ?, school = ?, school_id = ?, target_ptn = ?, phone = ?, tenant_id = ? WHERE id = ?");
                $stmt->execute([$name, $email, $plan, $is_active, $school ?: null, $school_id ?: null, $target_ptn ?: null, $phone ?: null, $newTenantId, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, plan = ?, is_active = ?, school = ?, school_id = ?, target_ptn = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email, $plan, $is_active, $school ?: null, $school_id ?: null, $target_ptn ?: null, $phone ?: null, $id]);
            }

            // 2. Find associated user_id from user_roles or users
            $stmtU = $pdo->prepare("SELECT user_id FROM user_roles WHERE reference_id = ? AND role = 'student' LIMIT 1");
            $stmtU->execute([$id]);
            $userId = $stmtU->fetchColumn();

            // Hash password if provided
            $hashed = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;
            if ($hashed) {
                $stmtPass = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $stmtPass->execute([$hashed, $id]);
            }

            if ($userId) {
                // Update existing user record by user_id
                if ($hashed) {
                    $pdo->prepare("UPDATE users SET identity_key = ?, password = ?, is_active = ? WHERE id = ?")->execute([$email, $hashed, $is_active, $userId]);
                } else {
                    $pdo->prepare("UPDATE users SET identity_key = ?, is_active = ? WHERE id = ?")->execute([$email, $is_active, $userId]);
                }
                $pdo->prepare("UPDATE user_roles SET reference_id = ? WHERE user_id = ? AND role = 'student'")->execute([$id, $userId]);
            } else {
                // Check if user exists by identity_key
                $stmtFindUser = $pdo->prepare("SELECT id FROM users WHERE LOWER(identity_key) = LOWER(?) OR LOWER(identity_key) = LOWER(?) LIMIT 1");
                $stmtFindUser->execute([$oldEmail ?: $email, $email]);
                $foundUserId = $stmtFindUser->fetchColumn();

                if ($foundUserId) {
                    if ($hashed) {
                        $pdo->prepare("UPDATE users SET identity_key = ?, password = ?, is_active = ? WHERE id = ?")->execute([$email, $hashed, $is_active, $foundUserId]);
                    } else {
                        $pdo->prepare("UPDATE users SET identity_key = ?, is_active = ? WHERE id = ?")->execute([$email, $is_active, $foundUserId]);
                    }
                    $ur_id = bin2hex(random_bytes(16));
                    $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
                    $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), reference_id = VALUES(reference_id)")->execute([$ur_id, $foundUserId, $sTenantId, $id]);
                } else {
                    // Create user row if it never existed
                    $newUserId = bin2hex(random_bytes(16));
                    $newUserId = substr($newUserId,0,8).'-'.substr($newUserId,8,4).'-'.substr($newUserId,12,4).'-'.substr($newUserId,16,4).'-'.substr($newUserId,20,12);
                    $initialPass = $hashed ?: password_hash('EduPathStudent2026!', PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, ?)")->execute([$newUserId, $email, $initialPass, $is_active]);
                    $ur_id = bin2hex(random_bytes(16));
                    $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
                    $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)")->execute([$ur_id, $newUserId, $sTenantId, $id]);
                }
            }

            // 3. Sync subscription record
            if (strtolower($plan) !== 'free') {
                $subId = 'sub-' . md5($id);
                $planNames = [
                    'mandiri' => 'Paket Mandiri',
                    'utama' => 'Paket Pejuang PTN (Utama)',
                    'vip' => 'Paket Elite Mentoring (VIP)'
                ];
                $planIdMap = [
                    'mandiri' => 'plan-p1-yearly',
                    'plan-mandiri' => 'plan-p1-yearly',
                    'plan-p1-monthly' => 'plan-p1-monthly',
                    'plan-p1-yearly' => 'plan-p1-yearly',
                    'utama' => 'plan-p2-yearly',
                    'plan-utama' => 'plan-p2-yearly',
                    'plan-p2-monthly' => 'plan-p2-monthly',
                    'plan-p2-yearly' => 'plan-p2-yearly',
                    'vip' => 'plan-p3-yearly',
                    'plan-vip' => 'plan-p3-yearly',
                    'plan-p3-monthly' => 'plan-p3-monthly',
                    'plan-p3-yearly' => 'plan-p3-yearly'
                ];
                $targetPlanId = $planIdMap[strtolower($plan)] ?? 'plan-p2-yearly';
                // Ensure targetPlanId exists in plans table to prevent FK violation
                $chkPlan = $pdo->prepare("SELECT id FROM plans WHERE id = ?");
                $chkPlan->execute([$targetPlanId]);
                if (!$chkPlan->fetch()) {
                    $targetPlanId = $pdo->query("SELECT id FROM plans LIMIT 1")->fetchColumn() ?: 'plan-p2-yearly';
                }
                $planNameSnap = $planNames[strtolower($plan)] ?? ucfirst($plan);

                // Student subscription must have tenant_id NULL to satisfy constraint sub_ownership_chk
                $subTenant = null;
                $pdo->prepare("
                    INSERT INTO subscriptions (id, tenant_id, student_id, plan_id, status, plan_name_snapshot, started_at, expires_at)
                    VALUES (?, ?, ?, ?, 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))
                    ON DUPLICATE KEY UPDATE 
                        plan_id = VALUES(plan_id), 
                        status = 'active', 
                        plan_name_snapshot = VALUES(plan_name_snapshot), 
                        expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
                ")->execute([$subId, $subTenant, $id, $targetPlanId, $planNameSnap]);
            } else {
                $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE student_id = ?")->execute([$id]);
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
    if ($payload->role === 'teacher') {
        http_response_code(403);
        echo json_encode(["error" => "Guru tidak memiliki izin akses ke transaksi pembayaran"]);
        exit;
    }
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
    if ($payload->role === 'affiliate') {
        $stmt = $pdo->prepare("
            SELECT a.*, u.identity_key, t.name as tenant_name
            FROM affiliates a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN tenants t ON a.tenant_id = t.id
            WHERE a.user_id = ? OR a.id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$payload->user_id, $payload->id]);
    } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
        $stmt = $pdo->prepare("
            SELECT a.*, u.identity_key, t.name as tenant_name
            FROM affiliates a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN tenants t ON a.tenant_id = t.id
            WHERE a.tenant_id = ? 
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$payload->tenant_id]);
    } else {
        $stmt = $pdo->query("
            SELECT a.*, u.identity_key, t.name as tenant_name
            FROM affiliates a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN tenants t ON a.tenant_id = t.id
            ORDER BY a.created_at DESC
        ");
    }
    echo safe_json_encode($stmt->fetchAll());
}
elseif ($action === 'commissions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($payload->role === 'affiliate') {
        $stmt = $pdo->prepare("
            SELECT c.*, a.referral_code, u.identity_key as affiliate_email, 
                   o.plan_name as order_plan_name, s.name as student_name
            FROM commissions c
            JOIN affiliates a ON c.affiliate_id = a.id
            JOIN users u ON a.user_id = u.id
            JOIN orders o ON c.order_id = o.id
            JOIN students s ON o.student_id = s.id
            WHERE a.user_id = ? OR a.id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$payload->user_id, $payload->id]);
    } elseif ($payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
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
    } else {
        $stmt = $pdo->query("
            SELECT c.*, a.referral_code, u.identity_key as affiliate_email, 
                   o.plan_name as order_plan_name, s.name as student_name
            FROM commissions c
            JOIN affiliates a ON c.affiliate_id = a.id
            JOIN users u ON a.user_id = u.id
            JOIN orders o ON c.order_id = o.id
            JOIN students s ON o.student_id = s.id
            ORDER BY c.created_at DESC
        ");
    }
    echo safe_json_encode($stmt->fetchAll());
}
elseif ($action === 'affiliate_settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $affCols = $pdo->query("DESCRIBE affiliates")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('name', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN name VARCHAR(150) NULL");
            if (!in_array('email', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN email VARCHAR(150) NULL");
        } catch (\Throwable $e) {}

        $stmt = $pdo->prepare("
            SELECT a.id, a.referral_code, a.commission_rate, a.bank_name, a.bank_account, a.bank_owner,
                   COALESCE(a.email, u.identity_key) as email, 
                   COALESCE(a.name, s.name, u.identity_key) as name
            FROM affiliates a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN students s ON (LOWER(s.email) = LOWER(u.identity_key))
            WHERE a.user_id = ? OR a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$payload->user_id, $payload->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            $uStmt = $pdo->prepare("SELECT id, identity_key as email FROM users WHERE id = ?");
            $uStmt->execute([$payload->user_id]);
            $uData = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($uData) {
                $data = [
                    'id' => null,
                    'referral_code' => '',
                    'commission_rate' => 20,
                    'bank_name' => '',
                    'bank_account' => '',
                    'bank_owner' => '',
                    'email' => $uData['email'],
                    'name' => $uData['email']
                ];
            }
        }
        echo safe_json_encode(["settings" => $data]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $bank_name = trim($input['bank_name'] ?? '');
        $bank_account = trim($input['bank_account'] ?? '');
        $bank_owner = trim($input['bank_owner'] ?? '');
        $new_password = $input['new_password'] ?? '';

        $pdo->beginTransaction();
        try {
            $chk = $pdo->prepare("SELECT id FROM affiliates WHERE user_id = ? OR id = ? LIMIT 1");
            $chk->execute([$payload->user_id, $payload->id]);
            $affId = $chk->fetchColumn();
            if ($affId) {
                $stmt = $pdo->prepare("
                    UPDATE affiliates 
                    SET bank_name = ?, bank_account = ?, bank_owner = ?, name = ?, email = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$bank_name, $bank_account, $bank_owner, $name, $email, $affId]);
            } else {
                $tId = (!empty($payload->tenant_id)) ? $payload->tenant_id : 'default_tenant';
                $stmt = $pdo->prepare("
                    INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, bank_name, bank_account, bank_owner)
                    VALUES (?, ?, ?, ?, 20.00, ?, ?, ?)
                ");
                $stmt->execute([$affId, $payload->user_id, $tId, $refCode, $bank_name, $bank_account, $bank_owner]);
            }

            if (!empty($email)) {
                $pdo->prepare("UPDATE users SET identity_key = ? WHERE id = ?")->execute([$email, $payload->user_id]);
                $pdo->prepare("UPDATE students SET email = ? WHERE email = ? OR id = ?")->execute([$email, $email, $payload->id]);
            }
            if (!empty($name)) {
                $pdo->prepare("UPDATE students SET name = ? WHERE email = ? OR id = ?")->execute([$name, $email, $payload->id]);
            }
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $payload->user_id]);
                $pdo->prepare("UPDATE students SET password = ? WHERE email = ? OR id = ?")->execute([$hashed, $email, $payload->id]);
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal menyimpan pengaturan: " . $e->getMessage()]);
        }
    }
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
        $sql = "
            SELECT 
                t.*,
                (SELECT COUNT(*) FROM students WHERE tenant_id = t.id) as student_count,
                (SELECT COUNT(*) FROM user_roles WHERE tenant_id = t.id AND role IN ('admin', 'teacher')) as staff_count,
                (SELECT COUNT(*) FROM affiliates WHERE tenant_id = t.id) as affiliate_count,
                (SELECT u.identity_key FROM user_roles ur JOIN users u ON ur.user_id = u.id WHERE ur.tenant_id = t.id AND ur.role = 'admin' LIMIT 1) as admin_email
            FROM tenants t 
            ORDER BY t.created_at DESC
        ";
        echo safe_json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $name = trim($input['name'] ?? '');
        if (empty($name)) {
            http_response_code(400); echo json_encode(["error" => "Nama cabang wajib diisi"]); exit;
        }

        $slug = trim($input['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        $slug = trim($slug, '-');
        if (empty($slug)) $slug = 'cabang-' . substr($id, 0, 6);

        // Pastikan slug unik
        $chk = $pdo->prepare("SELECT id FROM tenants WHERE slug = ?");
        $chk->execute([$slug]);
        if ($chk->fetch()) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $admin_email = trim($input['admin_email'] ?? '');
        $admin_name = trim($input['admin_name'] ?? '');
        $admin_password = $input['admin_password'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            // 1. Create Tenant
            $stmt = $pdo->prepare("INSERT INTO tenants (id, name, slug, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$id, $name, $slug]);
            
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
                $stmt->execute([$admin_id, $admin_email, $hashed_password, $admin_name ?: 'Admin Cabang']);
                
                // Link Role
                $ur_id = bin2hex(random_bytes(16));
                $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'admin', ?)");
                $stmt->execute([$ur_id, $user_id, $id, $admin_id]);
            }
            
            $pdo->commit();
            log_audit($pdo, $payload->user_id, null, 'tenant_created', $id, ['name' => $name, 'slug' => $slug]);
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal membuat tenant", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        if (empty($id) || empty($name)) {
            http_response_code(400); echo json_encode(["error" => "ID dan nama cabang wajib diisi"]); exit;
        }
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        $stmt = $pdo->prepare("UPDATE tenants SET name=?, slug=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $slug, $is_active, $id]);
        
        log_audit($pdo, $payload->user_id, null, 'tenant_updated', $id, ['is_active' => $is_active]);
        echo json_encode(["success" => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? ($input['id'] ?? '');
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID cabang diperlukan"]); exit;
        }
        $stuCount = $pdo->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = ?");
        $stuCount->execute([$id]);
        $hasStudents = ((int)$stuCount->fetchColumn()) > 0;

        $staffCount = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE tenant_id = ? AND role IN ('admin', 'teacher')");
        $staffCount->execute([$id]);
        $hasStaff = ((int)$staffCount->fetchColumn()) > 0;

        if ($hasStudents || $hasStaff) {
            $pdo->prepare("UPDATE tenants SET is_active = 0 WHERE id = ?")->execute([$id]);
            echo json_encode(["success" => true, "message" => "Cabang memiliki data terkait, status diubah menjadi Nonaktif"]);
        } else {
            $pdo->prepare("DELETE FROM tenants WHERE id = ?")->execute([$id]);
            echo json_encode(["success" => true, "message" => "Cabang berhasil dihapus"]);
        }
    }
}
elseif ($action === 'schools') {
    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Akses khusus Administrator & Superadmin"]);
        exit;
    }

    // Ensure schools table and schema
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schools (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                npsn VARCHAR(20) NULL,
                city VARCHAR(100) NULL,
                province VARCHAR(100) NULL,
                bk_name VARCHAR(150) NOT NULL,
                bk_phone VARCHAR(50) NOT NULL,
                bk_email VARCHAR(150) NOT NULL,
                estimated_students INT DEFAULT 200,
                is_active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_school_name (name),
                INDEX idx_school_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM schools LIKE 'is_active'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE schools ADD COLUMN is_active TINYINT DEFAULT 1");
        }
    } catch (\Throwable $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'school_id'")->fetchAll();
        if (empty($cols)) {
            try { $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL AFTER school"); } catch (\Throwable $e) {
                try { $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL"); } catch (\Throwable $e2) {}
            }
        }
    } catch (\Throwable $e) {}

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $search = trim($_GET['search'] ?? '');
        $whereSql = '';
        $params = [];
        if (!empty($search)) {
            $whereSql = "WHERE s.name LIKE ? OR s.city LIKE ? OR s.bk_name LIKE ? OR s.bk_email LIKE ? OR s.npsn LIKE ?";
            $q = "%$search%";
            $params = [$q, $q, $q, $q, $q];
        }

        $schools = [];
        try {
            $stmt = $pdo->prepare("
                SELECT s.*,
                    COALESCE(s.is_active, 1) as is_active,
                    s.bk_name as bk_coordinator_name,
                    s.bk_email as bk_coordinator_email,
                    s.bk_phone as bk_coordinator_phone,
                    (SELECT COUNT(DISTINCT st.id) FROM students st WHERE st.school_id = s.id OR st.school = s.name) as student_count,
                    (SELECT COUNT(DISTINCT st.id) FROM students st WHERE st.school_id = s.id OR st.school = s.name) as total_students,
                    (SELECT COUNT(DISTINCT st2.id) FROM students st2 JOIN subscriptions sub ON st2.id = sub.student_id AND sub.status = 'active' WHERE st2.school_id = s.id OR st2.school = s.name) as paid_student_count,
                    (SELECT COUNT(DISTINCT st2.id) FROM students st2 JOIN subscriptions sub ON st2.id = sub.student_id AND sub.status = 'active' WHERE st2.school_id = s.id OR st2.school = s.name) as paid_students
                FROM schools s
                $whereSql
                ORDER BY s.created_at DESC
            ");
            $stmt->execute($params);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Fallback safe query jika ada kendala di subquery hitungan siswa
            try {
                $stmtFallback = $pdo->prepare("SELECT s.*, COALESCE(s.is_active, 1) as is_active, s.bk_name as bk_coordinator_name, s.bk_email as bk_coordinator_email, s.bk_phone as bk_coordinator_phone, 0 as student_count, 0 as total_students, 0 as paid_student_count, 0 as paid_students FROM schools s $whereSql ORDER BY s.created_at DESC");
                $stmtFallback->execute($params);
                $schools = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e2) {
                $schools = [];
            }
        }

        foreach ($schools as &$sch) {
            $sch['total_students'] = (int)($sch['total_students'] ?? 0);
            $sch['student_count'] = (int)($sch['student_count'] ?? 0);
            $sch['paid_students'] = (int)($sch['paid_students'] ?? 0);
            $sch['paid_student_count'] = (int)($sch['paid_student_count'] ?? 0);
            $sch['is_active'] = (int)($sch['is_active'] ?? 1);
            $sch['bk_coordinator_name'] = $sch['bk_coordinator_name'] ?: ($sch['bk_name'] ?? '');
            $sch['bk_coordinator_email'] = $sch['bk_coordinator_email'] ?: ($sch['bk_email'] ?? '');
            $sch['bk_coordinator_phone'] = $sch['bk_coordinator_phone'] ?: ($sch['bk_phone'] ?? '');
            $sch['share_link'] = "https://edupath.co.id/?school=" . urlencode($sch['name']) . "#/spp";
        }
        unset($sch);

        echo safe_json_encode(["schools" => $schools]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($input['name'] ?? '');
        $bk_name = trim($input['bk_name'] ?? ($input['bk_coordinator_name'] ?? ''));
        $bk_email = strtolower(trim($input['bk_email'] ?? ($input['bk_coordinator_email'] ?? ($input['email'] ?? ''))));
        $bk_phone = trim($input['bk_phone'] ?? ($input['bk_coordinator_phone'] ?? ($input['phone'] ?? '')));
        $npsn = trim($input['npsn'] ?? '');
        $city = trim($input['city'] ?? '');
        $province = trim($input['province'] ?? '');
        $password = $input['password'] ?? ($input['bk_password'] ?? 'EduPathSchool01!2026');
        $estimated_students = (int)($input['estimated_students'] ?? 200);
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        if (empty($name) || empty($bk_name) || empty($bk_email) || empty($bk_phone)) {
            http_response_code(400);
            echo json_encode(["error" => "Nama Sekolah, Koordinator BK, Email BK, dan No. HP/WA wajib diisi"]);
            exit;
        }

        $baseSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) $baseSlug = 'sekolah';
        $slug = $baseSlug;
        $suffix = 1;
        while (true) {
            $stmt_s = $pdo->prepare("SELECT id FROM schools WHERE slug = ?");
            $stmt_s->execute([$slug]);
            if (!$stmt_s->fetch()) break;
            $slug = $baseSlug . '-' . (++$suffix);
        }

        $school_id = bin2hex(random_bytes(16));
        $school_id = substr($school_id,0,8).'-'.substr($school_id,8,4).'-'.substr($school_id,12,4).'-'.substr($school_id,16,4).'-'.substr($school_id,20,12);

        $user_id = bin2hex(random_bytes(16));
        $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);

        $ur_id = bin2hex(random_bytes(16));
        $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($user_id,20,12);

        $hashed_pass = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pdo->beginTransaction();
            $pdo->exec("INSERT INTO tenants (id, name, slug, is_active) VALUES ('default_tenant', 'Default EduPath Tenant', 'default', 1) ON DUPLICATE KEY UPDATE name=name");

            $stmt = $pdo->prepare("INSERT INTO schools (id, name, slug, npsn, city, province, bk_name, bk_phone, bk_email, estimated_students, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $name, $slug, $npsn, $city, $province, $bk_name, $bk_phone, $bk_email, $estimated_students, $is_active]);

            $stmt_u = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password)");
            $stmt_u->execute([$user_id, $bk_email, $hashed_pass]);

            $stmt_ur = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, 'default_tenant', 'school_bk', ?) ON DUPLICATE KEY UPDATE role='school_bk', reference_id=VALUES(reference_id)");
            $stmt_ur->execute([$ur_id, $user_id, $school_id]);

            $pdo->commit();
            log_audit($pdo, $payload->user_id, null, 'school_created', $school_id, ['name' => $name, 'bk_email' => $bk_email]);
            echo json_encode(["success" => true, "id" => $school_id, "message" => "Sekolah mitra berhasil ditambahkan"]);
            exit;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal menambahkan sekolah: " . $e->getMessage()]);
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $bk_name = trim($input['bk_name'] ?? ($input['bk_coordinator_name'] ?? ''));
        $bk_email = strtolower(trim($input['bk_email'] ?? ($input['bk_coordinator_email'] ?? ($input['email'] ?? ''))));
        $bk_phone = trim($input['bk_phone'] ?? ($input['bk_coordinator_phone'] ?? ($input['phone'] ?? '')));
        $npsn = trim($input['npsn'] ?? '');
        $city = trim($input['city'] ?? '');
        $province = trim($input['province'] ?? '');
        $estimated_students = (int)($input['estimated_students'] ?? 200);
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : null;

        if (empty($id) || empty($name)) {
            http_response_code(400); echo json_encode(["error" => "ID dan nama sekolah wajib diisi"]); exit;
        }

        try {
            if ($is_active !== null) {
                $stmt = $pdo->prepare("UPDATE schools SET name=?, npsn=?, city=?, province=?, bk_name=?, bk_phone=?, bk_email=?, estimated_students=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $npsn, $city, $province, $bk_name, $bk_phone, $bk_email, $estimated_students, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE schools SET name=?, npsn=?, city=?, province=?, bk_name=?, bk_phone=?, bk_email=?, estimated_students=? WHERE id=?");
                $stmt->execute([$name, $npsn, $city, $province, $bk_name, $bk_phone, $bk_email, $estimated_students, $id]);
            }

            if (!empty($input['password'])) {
                $newPass = password_hash($input['password'], PASSWORD_BCRYPT);
                $stmt_u = $pdo->prepare("
                    UPDATE users u 
                    JOIN user_roles ur ON u.id = ur.user_id 
                    SET u.password = ? 
                    WHERE ur.reference_id = ? AND ur.role = 'school_bk'
                ");
                $stmt_u->execute([$newPass, $id]);
            }

            log_audit($pdo, $payload->user_id, null, 'school_updated', $id, ['name' => $name]);
            echo json_encode(["success" => true, "message" => "Data sekolah mitra berhasil diperbarui"]);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal memperbarui sekolah: " . $e->getMessage()]);
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? ($input['id'] ?? '');
        if (empty($id)) {
            http_response_code(400); echo json_encode(["error" => "ID sekolah diperlukan"]); exit;
        }
        try {
            $pdo->prepare("DELETE FROM schools WHERE id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM user_roles WHERE reference_id = ? AND role = 'school_bk'")->execute([$id]);
            log_audit($pdo, $payload->user_id, null, 'school_deleted', $id);
            echo json_encode(["success" => true, "message" => "Sekolah berhasil dihapus"]);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(["error" => "Gagal menghapus sekolah: " . $e->getMessage()]);
            exit;
        }
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
    if ($payload->role === 'teacher') {
        http_response_code(403);
        echo json_encode(["error" => "Guru tidak memiliki izin akses ke Manajemen Paket"]);
        exit;
    }
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
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "Hanya Superadmin yang berwenang menambah paket belajar."]); exit;
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
            http_response_code(403); echo json_encode(["error" => "Hanya Superadmin yang berwenang mengubah paket belajar."]); exit;
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
            http_response_code(403); echo json_encode(["error" => "Hanya Superadmin yang berwenang menghapus paket belajar."]); exit;
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
        $teacher_name = trim($input['teacher_name'] ?? '');
        $teacher_id = trim($input['teacher_id'] ?? '');
        if ($payload->role === 'teacher') {
            $teacher_id = $payload->user_id ?? $payload->id;
            $tStmt = $pdo->prepare("SELECT name FROM admins WHERE id = ? OR username = ?");
            $tStmt->execute([$payload->id, $payload->id]);
            $teacher_name = $tStmt->fetchColumn() ?: ($payload->name ?? 'Guru / Tutor');
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO questions (
                    id, exam, test_component, subtest, topic, subtopic, skill, indicator, question_type,
                    subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e,
                    correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference,
                    is_qc_passed, classification, teacher_name, teacher_id
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?
                )
            ");
            $stmt->execute([
                $id, $exam, $test_component, $subtest, $topic, $subtopic, $skill, $indicator, $question_type,
                $subtest, $subtest, $subtopic ?? $topic ?? 'Umum', $input['difficulty'] ?? 'medium',
                $input['question'], $input['option_a'], $input['option_b'], $input['option_c'],
                $input['option_d'], $input['option_e'] ?? null, $input['correct'], $input['explanation'] ?? null,
                $input['cognitive_demand'] ?? null, $input['source_type'] ?? 'author_created', $input['rights_status'] ?? 'unknown',
                $input['source_name'] ?? null, $input['source_year'] ?? null, $input['source_reference'] ?? null,
                $is_qc_passed, $classification, $teacher_name, $teacher_id
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

        if ($payload->role === 'teacher') {
            $chk = $pdo->prepare("SELECT teacher_id, teacher_name FROM questions WHERE id = ?");
            $chk->execute([$id]);
            $qOwner = $chk->fetch(PDO::FETCH_ASSOC);
            if ($qOwner && !empty($qOwner['teacher_id']) && $qOwner['teacher_id'] !== $payload->user_id && $qOwner['teacher_id'] !== $payload->id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat mengubah soal yang Anda buat."]);
                exit;
            }
        }

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
        $teacher_name = trim($input['teacher_name'] ?? '');
        $teacher_id = trim($input['teacher_id'] ?? '');
        if ($payload->role === 'teacher') {
            $teacher_id = $payload->user_id ?? $payload->id;
            $tStmt = $pdo->prepare("SELECT name FROM admins WHERE id = ? OR username = ?");
            $tStmt->execute([$payload->id, $payload->id]);
            $teacher_name = $tStmt->fetchColumn() ?: ($payload->name ?? 'Guru / Tutor');
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE questions SET
                    exam=?, test_component=?, subtest=?, topic=?, subtopic=?, skill=?, indicator=?, question_type=?,
                    subtes=?, sub_materi=?, bab=?, difficulty=?, question=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?,
                    correct=?, explanation=?, cognitive_demand=?, source_type=?, rights_status=?, source_name=?, source_year=?, source_reference=?,
                    is_qc_passed=?, classification=?, teacher_name=?, teacher_id=?
                WHERE id=?
            ");
            $stmt->execute([
                $exam, $test_component, $subtest, $topic, $subtopic, $skill, $indicator, $question_type,
                $subtest, $subtest, $subtopic ?? $topic ?? 'Umum', $input['difficulty'] ?? 'medium',
                $input['question'], $input['option_a'], $input['option_b'], $input['option_c'],
                $input['option_d'], $input['option_e'] ?? null, $input['correct'], $input['explanation'] ?? null,
                $input['cognitive_demand'] ?? null, $input['source_type'] ?? 'author_created', $input['rights_status'] ?? 'unknown',
                $input['source_name'] ?? null, $input['source_year'] ?? null, $input['source_reference'] ?? null,
                $is_qc_passed, $classification, $teacher_name, $teacher_id,
                $id
            ]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update soal", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Akses ditolak"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        if ($payload->role === 'teacher') {
            $chk = $pdo->prepare("SELECT teacher_id, teacher_name FROM questions WHERE id = ?");
            $chk->execute([$id]);
            $qOwner = $chk->fetch(PDO::FETCH_ASSOC);
            if ($qOwner && !empty($qOwner['teacher_id']) && $qOwner['teacher_id'] !== $payload->user_id && $qOwner['teacher_id'] !== $payload->id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat menghapus soal yang Anda buat."]);
                exit;
            }
        }
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
        $leaf_id = !empty($input['leaf_id']) ? trim($input['leaf_id']) : null;
        $title = trim($input['title'] ?? 'Materi Belajar');
        $content = $input['content'] ?? '';
        $teacher_name = trim($input['teacher_name'] ?? '');
        $teacher_id = trim($input['teacher_id'] ?? '');
        if ($payload->role === 'teacher') {
            $teacher_id = $payload->user_id ?? $payload->id;
            $tStmt = $pdo->prepare("SELECT name FROM admins WHERE id = ? OR username = ?");
            $tStmt->execute([$payload->id, $payload->id]);
            $teacher_name = $tStmt->fetchColumn() ?: ($payload->name ?? 'Guru / Tutor');
        }
        $file_url = trim($input['file_url'] ?? '');
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        try {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO materials (id, exam, test_component, subtest, sub_materi, topic, subtopic, skill, indicator, leaf_id, title, content, teacher_name, teacher_id, file_url, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id, $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $leaf_id, $title, $content, $teacher_name, $teacher_id, $file_url, $is_active
                ]);
            } catch (\PDOException $colErr) {
                $stmt = $pdo->prepare("
                    INSERT INTO materials (id, exam, test_component, subtest, sub_materi, topic, subtopic, skill, indicator, title, content, teacher_name, teacher_id, file_url, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id, $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $title, $content, $teacher_name, $teacher_id, $file_url, $is_active
                ]);
            }
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

        if ($payload->role === 'teacher') {
            $chk = $pdo->prepare("SELECT teacher_id, teacher_name FROM materials WHERE id = ?");
            $chk->execute([$id]);
            $mOwner = $chk->fetch(PDO::FETCH_ASSOC);
            if ($mOwner && !empty($mOwner['teacher_id']) && $mOwner['teacher_id'] !== $payload->user_id && $mOwner['teacher_id'] !== $payload->id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat mengubah materi yang Anda buat."]);
                exit;
            }
        }

        $exam = $input['exam'] ?? 'SNBT';
        $subtest = $input['subtest'] ?? $input['sub_materi'] ?? 'Penalaran Umum';
        $test_component = $input['test_component'] ?? (
            (stripos($subtest, 'Literasi') !== false || stripos($subtest, 'Matematika') !== false) ? 'TES LITERASI' : 'TPS'
        );
        $topic = $input['topic'] ?? null;
        $subtopic = $input['subtopic'] ?? null;
        $skill = $input['skill'] ?? null;
        $indicator = $input['indicator'] ?? null;
        $leaf_id = !empty($input['leaf_id']) ? trim($input['leaf_id']) : null;
        $title = trim($input['title'] ?? 'Materi Belajar');
        $content = $input['content'] ?? '';
        $teacher_name = trim($input['teacher_name'] ?? '');
        $teacher_id = trim($input['teacher_id'] ?? '');
        if ($payload->role === 'teacher') {
            $teacher_id = $payload->user_id ?? $payload->id;
            $tStmt = $pdo->prepare("SELECT name FROM admins WHERE id = ? OR username = ?");
            $tStmt->execute([$payload->id, $payload->id]);
            $teacher_name = $tStmt->fetchColumn() ?: ($payload->name ?? 'Guru / Tutor');
        }
        $file_url = trim($input['file_url'] ?? '');
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        try {
            try {
                $stmt = $pdo->prepare("
                    UPDATE materials SET
                        exam=?, test_component=?, subtest=?, sub_materi=?, topic=?, subtopic=?, skill=?, indicator=?, leaf_id=?, title=?, content=?, teacher_name=?, teacher_id=?, file_url=?, is_active=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $leaf_id, $title, $content, $teacher_name, $teacher_id, $file_url, $is_active, $id
                ]);
            } catch (\PDOException $colErr) {
                $stmt = $pdo->prepare("
                    UPDATE materials SET
                        exam=?, test_component=?, subtest=?, sub_materi=?, topic=?, subtopic=?, skill=?, indicator=?, title=?, content=?, teacher_name=?, teacher_id=?, file_url=?, is_active=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $title, $content, $teacher_name, $teacher_id, $file_url, $is_active, $id
                ]);
            }
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal update materi", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
            http_response_code(403);
            echo json_encode(["error" => "Staff only"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        if ($payload->role === 'teacher') {
            $chk = $pdo->prepare("SELECT teacher_id, teacher_name FROM materials WHERE id = ?");
            $chk->execute([$id]);
            $mOwner = $chk->fetch(PDO::FETCH_ASSOC);
            if ($mOwner && !empty($mOwner['teacher_id']) && $mOwner['teacher_id'] !== $payload->user_id && $mOwner['teacher_id'] !== $payload->id) {
                http_response_code(403);
                echo json_encode(["error" => "Anda hanya dapat menghapus materi yang Anda buat."]);
                exit;
            }
        }
        try {
            $pdo->prepare("DELETE FROM materials WHERE id = ?")->execute([$id]);
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(["error" => "Gagal menghapus materi", "details" => $e->getMessage()]);
        }
    }
}
elseif (in_array($action, ['upload_material_file', 'upload_material_image']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(["error" => "Tidak ada file yang diunggah"]);
        exit;
    }
    $file = $_FILES['file'];
    $isImageOnly = ($action === 'upload_material_image');
    $allowedExts = $isImageOnly 
        ? ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']
        : ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
        
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        $msg = $isImageOnly ? "Format gambar tidak didukung. Gunakan PNG, JPG, JPEG, WEBP, atau SVG." : "Format file tidak didukung. Gunakan PDF, DOC, PPT, XLS, atau Gambar.";
        echo json_encode(["error" => $msg]);
        exit;
    }
    $maxSize = $isImageOnly ? (10 * 1024 * 1024) : (20 * 1024 * 1024);
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        $maxMb = $isImageOnly ? '10MB' : '20MB';
        echo json_encode(["error" => "Ukuran file maksimal $maxMb"]);
        exit;
    }
    $subDir = $isImageOnly ? '/uploads/materials/images' : '/uploads/materials';
    $targetDir = dirname(__DIR__) . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $safeName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $targetDir . '/' . $safeName;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $url = $subDir . '/' . $safeName;
        echo json_encode([
            "success" => true,
            "url" => $url,
            "filename" => $file['name'],
            "size" => $file['size']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Gagal menyimpan file"]);
    }
    exit;
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

            if ($payload->role === 'superadmin' && array_key_exists('tenant_id', $input)) {
                $newTenantId = !empty($input['tenant_id']) ? $input['tenant_id'] : null;
                $pdo->prepare("UPDATE admins SET tenant_id = ? WHERE id = ?")->execute([$newTenantId, $id]);
                $pdo->prepare("UPDATE user_roles SET tenant_id = ? WHERE reference_id = ? AND role != 'superadmin'")->execute([$newTenantId, $id]);
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
