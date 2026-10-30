<?php
// api/auth.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'register', 5, 30)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan pendaftaran. Coba lagi nanti."]);
        exit;
    }

    $name = safe_str($input['name'] ?? '');
    $email = strtolower(safe_str($input['email'] ?? ''));
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';
    $target_ptn = safe_str($input['target_ptn'] ?? '');
    $referral_code = safe_str($input['referral_code'] ?? '');
    $academic_program = safe_str($input['academic_program'] ?? 'SNPMB_UTBK');
    $target_level = strtoupper(safe_str($input['target_level'] ?? ''));
    $grade_level = safe_str($input['grade_level'] ?? '');
    $target_goal = safe_str($input['target_goal'] ?? ($input['target_school'] ?? $target_ptn));

    if (empty($target_level)) {
        if ($academic_program === 'TKA_SD') $target_level = 'SD';
        elseif ($academic_program === 'TKA_SMP') $target_level = 'SMP';
        elseif ($academic_program === 'TKA_SMA') $target_level = 'SMA';
        elseif ($academic_program === 'SNPMB_ALUMNI') $target_level = 'ALUMNI';
        else $target_level = 'SMA';
    }
    if (empty($target_ptn) && in_array($target_level, ['SMA', 'ALUMNI'])) {
        $target_ptn = $target_goal;
    }

    if (!$name || !$email || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Semua field harus diisi"]);
        exit;
    }
    if (strlen($email) > 254 || strlen($password) > 1024 || strlen($name) > 255) {
        http_response_code(400);
        echo json_encode(["error" => "Panjang data input melebihi batas yang diizinkan"]);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Format email tidak valid"]);
        exit;
    }

    // Cek email exist
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Email sudah terdaftar"]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $tenant_id = 'default_tenant'; // Default tenant inserted by install_db.php
    $user_id = bin2hex(random_bytes(16)); // UUID for users
    $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);

    // Resolve Affiliate Attribution with Anti-Fraud Self-Referral Guard
    $referred_by = null;
    if (!empty($referral_code)) {
        // Find affiliate by referral code AND ensure it matches the B2C tenant_id context
        $stmt_aff = $pdo->prepare("
            SELECT a.id, a.user_id, u.identity_key 
            FROM affiliates a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.referral_code = ? AND (a.tenant_id = ? OR a.tenant_id IS NULL OR a.tenant_id = '')
        ");
        $stmt_aff->execute([$referral_code, $tenant_id]);
        $aff = $stmt_aff->fetch();
        if ($aff) {
            $aff_identity = strtolower(trim($aff['identity_key'] ?? ''));
            // Anti-fraud: prevent self-referral if affiliate user identity matches registering email
            if ($aff_identity !== strtolower(trim($email))) {
                $referred_by = $aff['id'];
            }
        }
    }

    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

    try {
        $pdo->beginTransaction();
        
        // Cek apakah identity_key (email) sudah ada di tabel users (mencegah hijack admin ID)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(["error" => "Email sudah terdaftar di sistem utama"]);
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $email, $hashed_password]);
        }

        $school = trim($input['school'] ?? ($input['school_name'] ?? ''));
        $school_id = trim($input['school_id'] ?? '');
        if (!empty($school) && empty($school_id)) {
            try {
                $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $stmt_sch->execute([$school]);
                $found_id = $stmt_sch->fetchColumn();
                if ($found_id) $school_id = $found_id;
            } catch (\Throwable $e) {}
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, academic_program, target_level, grade_level, target_goal, school, school_id, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn ?: null, $academic_program, $target_level, $grade_level ?: null, $target_goal ?: null, $school ?: null, $school_id ?: null, $tenant_id, $referred_by]);
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, school, school_id, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $school ?: null, $school_id ?: null, $tenant_id, $referred_by]);
            } catch (PDOException $e2) {
                $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $tenant_id]);
            }
        }

        $ur_id = bin2hex(random_bytes(16));
        $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
        $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)");
        $stmt->execute([$ur_id, $user_id, $tenant_id, $id]);

        $pdo->commit();
        reset_rate_limit($pdo, 'register');

        // Generate Token
        $token = generate_jwt(['user_id' => $user_id, 'id' => $id, 'role' => 'student', 'tenant_id' => $tenant_id]);
        
        // Generate Refresh Token
        $refresh_token = bin2hex(random_bytes(32));
        $rt_hash = hash('sha256', $refresh_token);
        $device_id = $input['device_id'] ?? bin2hex(random_bytes(16));
        $rt_id = bin2hex(random_bytes(16));
        
        try {
            $rt_days = defined('JWT_REFRESH_TTL_DAYS') ? JWT_REFRESH_TTL_DAYS : 30;
            $pdo->prepare("INSERT INTO refresh_tokens (id, student_id, token_hash, device_id, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL $rt_days DAY))")
                ->execute([$rt_id, $id, $rt_hash, $device_id]);
        } catch (PDOException $e) { /* Migration might not be run yet, ignore */ }

        $csrf_token = generate_csrf_token();
        set_auth_cookies($token, $csrf_token);

        echo json_encode([
            "token"         => $token,          // Backward compat: Bearer untuk mobile
            "refresh_token" => $refresh_token,
            "csrf_token"    => $csrf_token,      // Simpan di memory/sessionStorage, bukan localStorage
            "user"          => [
                "id"                   => $id, 
                "name"                 => $name, 
                "email"                => $email, 
                "target_ptn"           => $target_ptn,
                "academic_program"     => $academic_program,
                "target_level"         => $target_level,
                "grade_level"          => $grade_level,
                "target_goal"          => $target_goal,
                "plan"                 => "free",
                "plan_name"            => "Paket Free / Trial (" . $academic_program . ")",
                "is_premium"           => false,
                "active_subscriptions" => []
            ]
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log('EduPath registration failed: ' . $e->getMessage());
        echo json_encode(["error" => "Gagal mendaftar. Silakan coba lagi."]);
    }
} 
elseif ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'login', 10, 15)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan login. Coba lagi nanti."]);
        exit;
    }

    $email = safe_str($input['email'] ?? '');
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';

    if (!check_rate_limit($pdo, 'login', 30, 15)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan login. Coba lagi nanti."]);
        exit;
    }

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["error" => "Email dan password harus diisi"]);
        exit;
    }
    if (strlen($email) > 254 || strlen($password) > 1024) {
        http_response_code(400);
        echo json_encode(["error" => "Format email atau password melebihi batas yang diizinkan."]);
        exit;
    }

    try {
    // Ensure schools table exists for join
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_school_name (name),
                INDEX idx_school_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        try { $pdo->exec("ALTER TABLE schools CONVERT TO CHARACTER SET utf8mb4"); } catch (\Throwable $e) {}
    } catch (\Throwable $e) {}

    // Ensure affiliates columns exist for backward-compatibility
    try {
        $affCols = $pdo->query("DESCRIBE affiliates")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('name', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN name VARCHAR(150) NULL");
        if (!in_array('email', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN email VARCHAR(150) NULL");
    } catch (\Throwable $e) {}

    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.identity_key, u.display_name as user_display_name, u.password as user_password, 
               s.password as student_password, s.email as student_email, a.password as admin_password,
               ur.role, ur.tenant_id, ur.reference_id, 
               COALESCE(s.id, a.id, sch.id, aff.id, ur.reference_id) as student_id, 
               COALESCE(NULLIF(TRIM(u.display_name), ''), NULLIF(TRIM(aff.name), ''), NULLIF(TRIM(s.name), ''), NULLIF(TRIM(aff.bank_owner), ''), NULLIF(TRIM(a.name), ''), NULLIF(TRIM(sch.name), ''), 'Pengguna EduPath') as name, 
               COALESCE(s.email, aff.email, a.username, sch.bk_email, u.identity_key) as email, 
               s.school as student_school,
               s.phone as student_phone,
               COALESCE(s.target_ptn, '') as target_ptn, 
               COALESCE(s.academic_program, 'SNPMB_UTBK') as academic_program,
               COALESCE(s.target_level, 'SMA') as target_level,
               COALESCE(s.grade_level, '') as grade_level,
               COALESCE(s.target_goal, s.target_ptn, '') as target_goal,
               COALESCE(s.plan, 'free') as plan,
               COALESCE(t.is_active, 1) as tenant_active,
               aff.id as affiliate_id,
               COALESCE(aff.referral_code, '') as referral_code,
               COALESCE(s.school_id, sch.id) as school_id,
               sch.name as school_name,
               sch.bk_name,
               sch.bk_email,
               sch.city as school_city,
               sch.slug as school_slug
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN students s ON (ur.reference_id = s.id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(s.email)))
        LEFT JOIN admins a ON (ur.reference_id = a.id OR u.identity_key = a.username)
        LEFT JOIN affiliates aff ON (ur.reference_id = aff.id OR u.id = aff.user_id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(aff.email)))
        LEFT JOIN schools sch ON (ur.reference_id = sch.id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(sch.bk_email)))
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE (LOWER(TRIM(u.identity_key)) = LOWER(TRIM(?)) 
            OR (s.email IS NOT NULL AND LOWER(TRIM(s.email)) = LOWER(TRIM(?))) 
            OR (aff.email IS NOT NULL AND LOWER(TRIM(aff.email)) = LOWER(TRIM(?))) 
            OR (a.username IS NOT NULL AND LOWER(TRIM(a.username)) = LOWER(TRIM(?)))
            OR (sch.bk_email IS NOT NULL AND LOWER(TRIM(sch.bk_email)) = LOWER(TRIM(?)))) 
          AND u.is_active = 1
        ORDER BY CASE 
            WHEN sch.bk_email IS NOT NULL AND LOWER(TRIM(sch.bk_email)) = LOWER(TRIM(?)) THEN 1
            WHEN sch.id IS NOT NULL THEN 2
            WHEN ur.role = 'school_bk' THEN 3
            WHEN ur.role = 'student' THEN 4
            WHEN ur.role = 'affiliate' THEN 5
            WHEN ur.role = 'teacher' THEN 6
            WHEN ur.role = 'admin' THEN 7
            WHEN ur.role = 'superadmin' THEN 8
            ELSE 9
        END ASC
        LIMIT 1
    ");
    $stmt->execute([$email, $email, $email, $email, $email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $password_matched = false;
    if ($user) {
        $candidates = array_filter([$user['user_password'] ?? '', $user['student_password'] ?? '', $user['admin_password'] ?? '']);
        foreach ($candidates as $stored_hash) {
            if (password_verify($password, $stored_hash)) {
                $password_matched = true;
                break;
            }
        }
    }

    if ($user && $password_matched) {
        // If account has school_bk role or matches registered school BK email
        if ($user['role'] === 'school_bk' || (!empty($user['bk_email']) && strtolower(trim($user['bk_email'])) === strtolower(trim($email)))) {
            $user['role'] = 'school_bk';
        }

        if (!in_array($user['role'], ['student', 'school_bk', 'teacher', 'tutor', 'affiliate', 'admin', 'superadmin'])) {
            http_response_code(403);
            echo json_encode(["error" => "Peran akun tidak dikenali."]);
            exit;
        }

        // Strict Separation: "semua role masuk melalui form login siswa kecuali admin dan super admin melalui /admin."
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            http_response_code(403);
            echo json_encode([
                "error" => "Akun Administrator & Superadmin wajib masuk melalui portal admin di /#/admin."
            ]);
            exit;
        }

        if ((int)$user['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }
        reset_rate_limit($pdo, 'login');
        
        // Update last login if student
        if ($user['student_id'] && $user['role'] === 'student') {
            $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?")->execute([$user['student_id']]);
        }
        
        $ref_id = $user['reference_id'] ?? $user['user_id'];
        $user_email = $user['email'] ?? ($user['student_email'] ?? ($user['identity_key'] ?? $email));
        if ($user['role'] === 'student') {
            $ref_id = $user['student_id'] ?? $user['reference_id'] ?? $user['user_id'];
        } elseif ($user['role'] === 'affiliate') {
            $ref_id = $user['affiliate_id'] ?? $user['reference_id'] ?? $user['user_id'];
        }

        $tenant_id_token = $user['tenant_id'];
        if ($user['role'] === 'superadmin') {
            $tenant_id_token = null;
            $user['tenant_id'] = null;
        }

        $token_payload = [
            'user_id'   => $user['user_id'], 
            'id'        => $ref_id, 
            'role'      => $user['role'], 
            'tenant_id' => $tenant_id_token,
            'email'     => $user_email
        ];

        if ($user['role'] === 'school_bk') {
            $token_payload['role'] = 'school_bk';
            $token_payload['school_id'] = $user['school_id'] ?? $user['reference_id'];
            $token_payload['school_name'] = $user['school_name'] ?? $user['name'];
            $user['school'] = [
                'id' => $user['school_id'] ?? $user['reference_id'],
                'name' => $user['school_name'] ?? $user['name'],
                'slug' => $user['school_slug'] ?? '',
                'bk_name' => $user['bk_name'] ?? $user['name'],
                'bk_email' => $user_email,
                'city' => $user['school_city'] ?? '',
                'share_link' => "https://edupath.co.id/?school=" . urlencode($user['school_name'] ?? $user['name']) . "#/spp"
            ];
        }

        $token = generate_jwt($token_payload);
        
        // Generate Refresh Token
        $refresh_token = bin2hex(random_bytes(32));
        $rt_hash = hash('sha256', $refresh_token);
        $device_id = $input['device_id'] ?? bin2hex(random_bytes(16));
        $rt_id = bin2hex(random_bytes(16));
        
        try {
            // Revoke old tokens for this specific device if it exists to prevent bloat
            $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW() WHERE student_id = ? AND device_id = ? AND revoked_at IS NULL")
                ->execute([$user['reference_id'], $device_id]);
                
            $rt_days = defined('JWT_REFRESH_TTL_DAYS') ? JWT_REFRESH_TTL_DAYS : 30;
            $pdo->prepare("INSERT INTO refresh_tokens (id, student_id, token_hash, device_id, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL $rt_days DAY))")
                ->execute([$rt_id, $user['reference_id'], $rt_hash, $device_id]);
        } catch (PDOException $e) { /* Migration not run yet */ }

        $csrf_token = generate_csrf_token();
        set_auth_cookies($token, $csrf_token);

        unset($user['user_password'], $user['student_password'], $user['password']);
        $user['email'] = $user_email;
        $user['id'] = $ref_id;
        if ($user['role'] === 'student') {
            $user['school'] = $user['student_school'] ?? '';
            $user['phone'] = $user['student_phone'] ?? '';
            $user['academic_program'] = $user['academic_program'] ?? 'SNPMB_UTBK';
            $user['target_level'] = $user['target_level'] ?? 'SMA';
            $user['grade_level'] = $user['grade_level'] ?? '';
            $user['target_goal'] = $user['target_goal'] ?? ($user['target_ptn'] ?? '');

            // Check active subscriptions in subscriptions table (multi-package support)
            $has_active_sub = false;
            $active_subs = [];
            try {
                $stmtSubs = $pdo->prepare("
                    SELECT sub.id, sub.plan_id, sub.plan_name_snapshot, 
                           COALESCE(sub.academic_program, p.academic_program, 'SNPMB/UTBK') as academic_program,
                           sub.status, sub.expires_at, sub.started_at
                    FROM subscriptions sub
                    LEFT JOIN plans p ON sub.plan_id = p.id
                    WHERE (sub.student_id = ? OR sub.student_id = ?) 
                      AND sub.status = 'active' 
                      AND (sub.expires_at IS NULL OR sub.expires_at > NOW())
                    ORDER BY sub.expires_at DESC
                ");
                $stmtSubs->execute([$ref_id, $user['user_id']]);
                $active_subs = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($active_subs)) {
                    $has_active_sub = true;
                    if (!empty($active_subs[0]['plan_name_snapshot'])) {
                        $user['plan_name'] = $active_subs[0]['plan_name_snapshot'];
                    }
                }
            } catch (\Throwable $e) {}
            $user['active_subscriptions'] = $active_subs;

            $plan = strtolower($user['plan'] ?? 'free');
            $paid_keywords = ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium', 'intensif', 'snbt', 'p1', 'p2', 'p3'];
            $is_paid_plan = false;
            foreach ($paid_keywords as $kw) {
                if (str_contains($plan, $kw)) {
                    $is_paid_plan = true;
                    break;
                }
            }
            $user['is_premium'] = $is_paid_plan || $has_active_sub;
            $plan_labels = [
                'mandiri' => 'Paket Mandiri',
                'starter' => 'Paket Mandiri',
                'utama'   => 'Paket Pejuang PTN (Utama)',
                'pejuang' => 'Paket Pejuang PTN (Utama)',
                'vip'     => 'Paket Elite Mentoring (VIP)',
                'elite'   => 'Paket Elite Mentoring (VIP)',
                'free'    => 'Paket Free / Trial'
            ];
            if (empty($user['plan_name'])) {
                $user['plan_name'] = $plan_labels[$plan] ?? ucfirst($plan);
            }
            if ($user['is_premium'] && ($user['plan_name'] === 'Paket Free / Trial' || $user['plan_name'] === 'Free')) {
                $user['plan_name'] = 'Paket Mandiri';
            }

            // Bersihkan nama siswa: Jangan pernah menampilkan email sebagai nama
            $cleanName = trim($user['name'] ?? '');
            if (empty($cleanName) || str_contains($cleanName, '@')) {
                $cleanName = 'Siswa EduPath';
            }
            $user['name'] = $cleanName;
        } elseif ($user['role'] === 'affiliate') {
            $user['id'] = $ref_id;
            $user['affiliate_id'] = $ref_id;
            $cleanName = trim($user['user_display_name'] ?? ($user['name'] ?? ''));
            if (empty($cleanName) || str_contains($cleanName, '@') || $cleanName === 'Pengguna EduPath') {
                $emailPrefix = explode('@', $user_email)[0];
                $cleanName = !empty($user['referral_code']) ? $user['referral_code'] : ucfirst($emailPrefix);
            }
            $user['name'] = $cleanName;
            $user['display_name'] = $cleanName;
            $user['referral_code'] = $user['affiliate_code'] ?? ($user['referral_code'] ?? '');
            $user['is_premium'] = true;
            $user['plan'] = 'affiliate';
            $user['plan_name'] = 'Mitra Afiliasi Resmi';
        } elseif ($user['role'] === 'school_bk') {
            $cleanName = trim($user['user_display_name'] ?? ($user['name'] ?? ''));
            if (empty($cleanName) || str_contains($cleanName, '@') || $cleanName === 'Pengguna EduPath') {
                $cleanName = !empty($user['school_name']) ? ("Guru BK " . $user['school_name']) : 'Koordinator BK';
            }
            $user['name'] = $cleanName;
            $user['display_name'] = $cleanName;
            $user['is_premium'] = true;
            $user['plan'] = 'school_partner';
            $user['plan_name'] = 'Sekolah Mitra Resmi';
        } elseif (in_array($user['role'], ['teacher', 'guru', 'tutor'])) {
            $cleanName = trim($user['user_display_name'] ?? ($user['name'] ?? ''));
            if (empty($cleanName) || str_contains($cleanName, '@') || $cleanName === 'Pengguna EduPath') {
                $emailPrefix = explode('@', $user_email)[0];
                $cleanName = 'Pengajar ' . ucfirst($emailPrefix);
            }
            $user['name'] = $cleanName;
            $user['display_name'] = $cleanName;
            $user['is_premium'] = true;
            $user['plan'] = 'teacher';
            $user['plan_name'] = 'Tenaga Pendidik / Tutor';
        }

        echo json_encode([
            "token"         => $token,          // Backward compat: Bearer untuk mobile
            "refresh_token" => $refresh_token,
            "csrf_token"    => $csrf_token,
            "user"          => $user
        ]);
    } else {
        log_audit($pdo, null, null, 'student_login_failed', null, ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Email atau password salah"]); // Web app compatibility
    }
} catch (\Throwable $e) {
    http_response_code(500);
    error_log("Auth login error: " . $e->getMessage());
    $isDev = (defined('APP_ENV') && APP_ENV === 'development');
    $errMsg = $isDev ? ("Terjadi kendala saat login: " . $e->getMessage()) : "Terjadi kendala internal server saat memproses login.";
    echo json_encode(["error" => $errMsg]);
    exit;
}
}
elseif ($action === 'refresh' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'refresh', 20, 15)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak permintaan refresh."]);
        exit;
    }

    $refresh_token = $input['refresh_token'] ?? '';
    if (empty($refresh_token)) {
        http_response_code(400);
        echo json_encode(["error" => "Refresh token wajib diset"]);
        exit;
    }

    $rt_hash = hash('sha256', $refresh_token);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > NOW()");
        $stmt->execute([$rt_hash]);
        $session = $stmt->fetch();

        if ($session) {
            // Valid refresh token
            reset_rate_limit($pdo, 'refresh');
            
            // Revoke current token to rotate
            $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW(), last_used_at = NOW() WHERE id = ?")
                ->execute([$session['id']]);
                
            // Issue new JWT
            // Get current active role and tenant via reference_id
            $u_stmt = $pdo->prepare("
                SELECT ur.user_id, ur.tenant_id, t.is_active as tenant_active 
                FROM user_roles ur
                LEFT JOIN tenants t ON ur.tenant_id = t.id
                WHERE ur.reference_id = ? AND ur.role = 'student' LIMIT 1
            ");
            $u_stmt->execute([$session['student_id']]);
            $ur = $u_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ur && (int)$ur['tenant_active'] === 0) {
                http_response_code(403);
                echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
                exit;
            }
            
            $u_tenant = $ur['tenant_id'] ?? '553af312-fb50-4e24-93ee-0d1abd52a62d';
            $user_id_claim = $ur['user_id'] ?? $session['student_id'];
            
            $token = generate_jwt(['user_id' => $user_id_claim, 'id' => $session['student_id'], 'role' => 'student', 'tenant_id' => $u_tenant]);
            
            // Issue new Refresh Token
            $new_refresh_token = bin2hex(random_bytes(32));
            $new_rt_hash = hash('sha256', $new_refresh_token);
            $rt_id = bin2hex(random_bytes(16));
            
            $rt_days = defined('JWT_REFRESH_TTL_DAYS') ? JWT_REFRESH_TTL_DAYS : 30;
            $pdo->prepare("INSERT INTO refresh_tokens (id, student_id, token_hash, device_id, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL $rt_days DAY))")
                ->execute([$rt_id, $session['student_id'], $new_rt_hash, $session['device_id']]);
                
            $csrf_token = generate_csrf_token();
            set_auth_cookies($token, $csrf_token);

            echo json_encode([
                "token"         => $token,
                "refresh_token" => $new_refresh_token,
                "csrf_token"    => $csrf_token,
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Refresh token tidak valid atau kadaluarsa"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Server error"]);
    }
}
elseif ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payload = authenticate();
        $refresh_token = $input['refresh_token'] ?? '';
        if (!empty($refresh_token) && !empty($payload->id)) {
            $rt_hash = hash('sha256', $refresh_token);
            $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW() WHERE token_hash = ? AND student_id = ?")
                ->execute([$rt_hash, $payload->id]);
        }
    } catch (\Throwable $e) {}
    
    clear_auth_cookies();
    echo json_encode(["success" => true]);
    exit;
}
elseif ($action === 'update_fcm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = authenticate();
    if ($payload->role !== 'student') {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }

    $fcm_token = trim($input['fcm_token'] ?? '');
    $device_id = trim($input['device_id'] ?? '');
    $platform = trim($input['platform'] ?? 'android');

    if (empty($fcm_token) || empty($device_id)) {
        http_response_code(400);
        echo json_encode(["error" => "fcm_token dan device_id diperlukan"]);
        exit;
    }

    try {
        $id = bin2hex(random_bytes(16));
        // Upsert logic: If device_id exists, update fcm_token and last_seen. Otherwise insert.
        $stmt = $pdo->prepare("
            INSERT INTO device_tokens (id, student_id, fcm_token, device_id, platform, last_seen_at) 
            VALUES (?, ?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            fcm_token = VALUES(fcm_token), 
            student_id = VALUES(student_id),
            last_seen_at = NOW(), 
            is_active = 1
        ");
        $stmt->execute([$id, $payload->id, $fcm_token, $device_id, $platform]);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal menyimpan fcm_token"]);
    }
}
elseif ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = authenticate();
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $school = trim($input['school'] ?? '');
    $school_id = trim($input['school_id'] ?? '');

    // Fetch existing user data
    $userId = $payload->user_id ?? $payload->id;
    $stmtCur = $pdo->prepare("SELECT identity_key, display_name FROM users WHERE id = ? LIMIT 1");
    $stmtCur->execute([$userId]);
    $curUser = $stmtCur->fetch(PDO::FETCH_ASSOC);

    $currentEmail = strtolower(trim($curUser['identity_key'] ?? ($payload->email ?? '')));
    $currentName = trim($curUser['display_name'] ?? '');

    // Partial update fallback: if name is empty, retain current name
    if (empty($name)) {
        $name = !empty($currentName) ? $currentName : 'Pengguna EduPath';
    }

    // Partial update fallback: if email is empty, retain current email
    if (empty($email)) {
        $email = $currentEmail;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Format email tidak valid"]);
        exit;
    }

    // Only check email conflict if email is actually changing
    if ($email !== $currentEmail) {
        $stmtConflict = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(identity_key)) = LOWER(TRIM(?)) AND id != ? LIMIT 1");
        $stmtConflict->execute([$email, $userId]);
        if ($stmtConflict->fetch()) {
            http_response_code(409);
            echo json_encode(["error" => "Email sudah digunakan oleh akun lain"]);
            exit;
        }
    }

    if (!empty($school) && empty($school_id)) {
        try {
            $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt_sch->execute([$school]);
            $found_id = $stmt_sch->fetchColumn();
            if ($found_id) $school_id = $found_id;
        } catch (\Throwable $e) {}
    }

    $hashed_password = null;
    if (!empty($password)) {
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "Password minimal 6 karakter"]);
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    }

    // Role-specific table updates
    $isStaffRole = in_array($payload->role, ['admin', 'superadmin', 'teacher', 'guru', 'tutor']);
    if ($payload->role === 'affiliate') {
        $bank_name = trim($input['bank_name'] ?? '');
        $bank_account = trim($input['bank_account'] ?? '');
        $bank_owner = trim($input['bank_owner'] ?? '');
        try {
            $pdo->prepare("UPDATE affiliates SET name = ?, email = ?, bank_name = ?, bank_account = ?, bank_owner = ? WHERE id = ? OR user_id = ?")
                ->execute([$name, $email, $bank_name, $bank_account, $bank_owner, $payload->id, $userId]);
        } catch (\Throwable $e) {}
    } elseif ($payload->role === 'school_bk') {
        $phone = trim($input['phone'] ?? '');
        $schId = $payload->school_id ?? $payload->reference_id ?? $payload->id;
        try {
            $pdo->prepare("UPDATE schools SET bk_name = ?, bk_email = ?, bk_phone = ? WHERE id = ?")
                ->execute([$name, $email, $phone, $schId]);
        } catch (\Throwable $e) {}
    } elseif ($isStaffRole) {
        if ($hashed_password) {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ?, password = ? WHERE id = ? OR username = ?");
            $stmt->execute([$name, $email, $hashed_password, $payload->id, $currentEmail]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ? WHERE id = ? OR username = ?");
            $stmt->execute([$name, $email, $payload->id, $currentEmail]);
        }
    } else {
        // Student
        $target_ptn = trim($input['target_ptn'] ?? '');
        $target_goal = trim($input['target_goal'] ?? ($target_ptn ?: ''));
        $academic_program = trim($input['academic_program'] ?? '');
        $target_level = strtoupper(trim($input['target_level'] ?? ''));
        $grade_level = trim($input['grade_level'] ?? '');
        $phone = trim($input['phone'] ?? '');

        if ($hashed_password) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET name = ?, email = ?, password = ?, school = ?, school_id = ?, 
                        target_ptn = COALESCE(NULLIF(?, ''), target_ptn),
                        target_goal = COALESCE(NULLIF(?, ''), target_goal),
                        academic_program = COALESCE(NULLIF(?, ''), academic_program),
                        target_level = COALESCE(NULLIF(?, ''), target_level),
                        grade_level = COALESCE(NULLIF(?, ''), grade_level),
                        phone = COALESCE(NULLIF(?, ''), phone)
                    WHERE id = ? OR id = ? OR id = (SELECT reference_id FROM user_roles WHERE user_id = ? LIMIT 1) OR LOWER(email) = LOWER(?)
                ");
                $stmt->execute([
                    $name, $email, $hashed_password, $school ?: null, $school_id ?: null,
                    $target_ptn, $target_goal, $academic_program, $target_level, $grade_level, $phone,
                    $payload->id, $userId, $userId, $currentEmail
                ]);
            } catch (\Throwable $e) {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, password = ? WHERE id = ? OR id = ? OR id = (SELECT reference_id FROM user_roles WHERE user_id = ? LIMIT 1) OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $hashed_password, $payload->id, $userId, $userId, $currentEmail]);
            }
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET name = ?, email = ?, school = ?, school_id = ?, 
                        target_ptn = COALESCE(NULLIF(?, ''), target_ptn),
                        target_goal = COALESCE(NULLIF(?, ''), target_goal),
                        academic_program = COALESCE(NULLIF(?, ''), academic_program),
                        target_level = COALESCE(NULLIF(?, ''), target_level),
                        grade_level = COALESCE(NULLIF(?, ''), grade_level),
                        phone = COALESCE(NULLIF(?, ''), phone)
                    WHERE id = ? OR id = ? OR id = (SELECT reference_id FROM user_roles WHERE user_id = ? LIMIT 1) OR LOWER(email) = LOWER(?)
                ");
                $stmt->execute([
                    $name, $email, $school ?: null, $school_id ?: null,
                    $target_ptn, $target_goal, $academic_program, $target_level, $grade_level, $phone,
                    $payload->id, $userId, $userId, $currentEmail
                ]);
            } catch (\Throwable $e) {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE id = ? OR id = ? OR id = (SELECT reference_id FROM user_roles WHERE user_id = ? LIMIT 1) OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $payload->id, $userId, $userId, $currentEmail]);
            }
        }
    }

    // Always update users identity_key and display_name
    if ($hashed_password) {
        $stmt = $pdo->prepare("UPDATE users SET identity_key = ?, password = ?, display_name = ? WHERE id = ?");
        $stmt->execute([$email, $hashed_password, $name, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET identity_key = ?, display_name = ? WHERE id = ?");
        $stmt->execute([$email, $name, $userId]);
    }

    // Generate refreshed JWT token with new credentials
    $new_payload = [
        'user_id'   => $userId,
        'id'        => $payload->id,
        'role'      => $payload->role,
        'tenant_id' => $payload->tenant_id ?? null,
        'email'     => $email
    ];
    if (!empty($school_id)) $new_payload['school_id'] = $school_id;
    $new_token = generate_jwt($new_payload);
    $new_csrf = generate_csrf_token();
    set_auth_cookies($new_token, $new_csrf);

    echo json_encode([
        "success" => true,
        "message" => "Profil berhasil diperbarui",
        "token"   => $new_token,
        "csrf_token" => $new_csrf,
        "user"    => [
            "id"           => $payload->id,
            "user_id"      => $userId,
            "name"         => $name,
            "display_name" => $name,
            "email"        => $email,
            "school"       => $school,
            "school_id"    => $school_id,
            "role"         => $payload->role,
            "tenant_id"    => $payload->tenant_id ?? null
        ]
    ]);
    exit;
}
elseif ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = authenticate();

    // School BK Coordinator
    if ($payload->role === 'school_bk') {
        $schId = $payload->school_id ?? $payload->reference_id ?? $payload->id;
        $stmt = $pdo->prepare("SELECT s.*, u.display_name FROM schools s LEFT JOIN user_roles ur ON s.id = ur.reference_id LEFT JOIN users u ON ur.user_id = u.id WHERE s.id = ? OR LOWER(s.bk_email) = LOWER(?) LIMIT 1");
        $stmt->execute([$schId, $payload->email ?? '']);
        $sch = $stmt->fetch(PDO::FETCH_ASSOC);
        $bkName = $sch['display_name'] ?? ($sch['bk_name'] ?? ($sch['bk_coordinator_name'] ?? ($sch['name'] ?? 'Koordinator BK')));
        $bkEmail = $sch['bk_email'] ?? $sch['bk_coordinator_email'] ?? ($payload->email ?? '');
        $bkPhone = $sch['bk_phone'] ?? $sch['bk_coordinator_phone'] ?? '';
        $user = [
            'id' => $payload->id,
            'name' => $bkName,
            'display_name' => $bkName,
            'email' => $bkEmail,
            'phone' => $bkPhone,
            'role' => 'school_bk',
            'school_id' => $sch['id'] ?? $schId,
            'school_name' => $sch['name'] ?? '',
            'school' => $sch,
            'is_premium' => true,
            'tenant_id' => $payload->tenant_id ?? 'default_tenant'
        ];
        header('Cache-Control: private, max-age=30');
        echo json_encode(["user" => $user]);
        exit;
    }

    // Staff (Admin, Superadmin, Teacher, Guru, Tutor)
    if (in_array($payload->role, ['admin', 'superadmin', 'teacher', 'guru', 'tutor'])) {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.identity_key, u.display_name, ur.role, ur.tenant_id, a.id as admin_id, a.username, 
                   COALESCE(NULLIF(TRIM(u.display_name), ''), a.name, t.name, u.identity_key) as name,
                   t.name as tenant_name
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN admins a ON (ur.reference_id = a.id OR u.identity_key = a.username)
            LEFT JOIN tenants t ON ur.tenant_id = t.id
            WHERE u.id = ? OR a.id = ? OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(?))
            LIMIT 1
        ");
        $stmt->execute([$payload->user_id ?? $payload->id, $payload->id, $payload->email ?? '']);
        $adm = $stmt->fetch(PDO::FETCH_ASSOC);

        $name = $adm['name'] ?? '';
        if (empty($name) || $name === ($adm['identity_key'] ?? '')) {
            if ($payload->role === 'superadmin') $name = 'Super Administrator';
            elseif ($payload->role === 'admin') $name = !empty($adm['tenant_name']) ? ('Admin ' . $adm['tenant_name']) : 'Administrator';
            elseif (in_array($payload->role, ['teacher', 'guru', 'tutor'])) $name = 'Guru / Tutor';
        }

        $user = [
            'id'          => $adm['admin_id'] ?? $payload->id,
            'name'        => $name,
            'display_name'=> $name,
            'email'       => $adm['username'] ?? ($adm['identity_key'] ?? ($payload->email ?? '')),
            'role'        => $payload->role,
            'is_premium'  => true,
            'tenant_id'   => $payload->tenant_id,
            'tenant_name' => $adm['tenant_name'] ?? null
        ];
        header('Cache-Control: private, max-age=30');
        echo json_encode(["user" => $user]);
        exit;
    }

    // Mitra Afiliasi
    if ($payload->role === 'affiliate') {
        $stmt = $pdo->prepare("SELECT a.*, u.display_name FROM affiliates a LEFT JOIN users u ON (a.user_id = u.id OR a.id = u.id) WHERE a.id = ? OR a.user_id = ? OR LOWER(TRIM(a.email)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->execute([$payload->id, $payload->user_id ?? '', $payload->email ?? '']);
        $aff = $stmt->fetch(PDO::FETCH_ASSOC);

        $cleanName = trim($aff['display_name'] ?? ($aff['name'] ?? ($aff['bank_owner'] ?? '')));
        if (empty($cleanName) || str_contains($cleanName, '@') || $cleanName === 'Mitra Afiliasi') {
            $prefix = explode('@', $aff['email'] ?? ($payload->email ?? ''))[0];
            $cleanName = !empty($aff['referral_code']) ? $aff['referral_code'] : ucfirst($prefix);
        }

        $user = [
            'id'            => $aff['id'] ?? $payload->id,
            'name'          => $cleanName,
            'display_name'  => $cleanName,
            'email'         => $aff['email'] ?? ($payload->email ?? ''),
            'role'          => 'affiliate',
            'referral_code' => $aff['referral_code'] ?? '',
            'bank_name'     => $aff['bank_name'] ?? '',
            'bank_account'  => $aff['bank_account'] ?? '',
            'bank_owner'    => $aff['bank_owner'] ?? '',
            'is_premium'    => true,
            'plan'          => 'affiliate',
            'plan_name'     => 'Mitra Afiliasi Resmi',
            'tenant_id'     => $payload->tenant_id ?? 'default_tenant'
        ];
        header('Cache-Control: private, max-age=30');
        echo json_encode(["user" => $user]);
        exit;
    }

    // Student: query biasa + subscription check
    $stmt = $pdo->prepare("
        SELECT s.*,
               COALESCE(s.email, u.identity_key) as email,
               (SELECT COUNT(*) > 0
                FROM subscriptions sub
                WHERE sub.student_id = s.id
                  AND sub.status = 'active'
                  AND sub.expires_at > NOW()
                LIMIT 1) AS has_sub
        FROM students s
        LEFT JOIN user_roles ur ON ur.reference_id = s.id AND ur.role = 'student'
        LEFT JOIN users u ON (u.id = ur.user_id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(s.email)))
        WHERE s.id = ?
           OR s.id = ?
           OR ur.user_id = ?
           OR (s.email IS NOT NULL AND LOWER(TRIM(s.email)) = LOWER(TRIM(?)))
           OR (u.identity_key IS NOT NULL AND LOWER(TRIM(u.identity_key)) = LOWER(TRIM(?)))
        LIMIT 1
    ");
    $stmt->execute([$payload->id, $payload->user_id ?? '', $payload->user_id ?? '', $payload->email ?? '', $payload->email ?? '']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        unset($user['password']);

        // Guarantee email is NEVER empty
        if (empty(trim($user['email'] ?? ''))) {
            $user['email'] = $payload->email ?? '';
        }
        if (empty(trim($user['email'] ?? '')) && !empty($payload->user_id)) {
            try {
                $stmtU = $pdo->prepare("SELECT identity_key FROM users WHERE id = ?");
                $stmtU->execute([$payload->user_id]);
                $uEmail = $stmtU->fetchColumn();
                if (!empty($uEmail)) {
                    $user['email'] = $uEmail;
                    $pdo->prepare("UPDATE students SET email = ? WHERE id = ?")->execute([$uEmail, $user['id']]);
                }
            } catch (\Throwable $e) {}
        }

        if (empty(trim($user['name'] ?? '')) && !empty($payload->user_id)) {
            try {
                $stmtU = $pdo->prepare("SELECT display_name FROM users WHERE id = ?");
                $stmtU->execute([$payload->user_id]);
                $uName = $stmtU->fetchColumn();
                if (!empty(trim($uName ?? ''))) {
                    $user['name'] = trim($uName);
                    $pdo->prepare("UPDATE students SET name = ? WHERE id = ?")->execute([$user['name'], $user['id']]);
                }
            } catch (\Throwable $e) {}
        }
        $plan = strtolower($user['plan'] ?? 'free');
        $is_paid_plan = false;
        $paid_keywords = ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium', 'intensif', 'snbt', 'p1', 'p2', 'p3'];
        foreach ($paid_keywords as $kw) {
            if (str_contains($plan, $kw)) {
                $is_paid_plan = true;
                break;
            }
        }
        $user['is_premium']  = $is_paid_plan || (bool)($user['has_sub'] ?? false);
        $user['role']        = 'student';
        $user['tenant_id']   = $payload->tenant_id ?? ($user['tenant_id'] ?? 'default_tenant');
        
        // Bersihkan nama siswa: Jangan tampilkan email sebagai nama
        $rawStudentName = trim($user['name'] ?? '');
        if (empty($rawStudentName) || str_contains($rawStudentName, '@')) {
            $rawStudentName = 'Siswa EduPath';
        }
        $user['name']        = $rawStudentName;
        $user['school']      = $user['school'] ?? '';
        $user['school_id']   = $user['school_id'] ?? null;
        $user['target_ptn']  = $user['target_ptn'] ?? '';
        $user['academic_program'] = $user['academic_program'] ?? 'SNPMB_UTBK';
        $user['target_level'] = $user['target_level'] ?? 'SMA';
        $user['grade_level'] = $user['grade_level'] ?? '';
        $user['target_goal'] = $user['target_goal'] ?? ($user['target_ptn'] ?? '');
        $user['phone']       = $user['phone'] ?? '';

        // Fetch multi-package active subscriptions
        $active_subs = [];
        try {
            $stmtSubs = $pdo->prepare("
                SELECT sub.id, sub.plan_id, sub.plan_name_snapshot, 
                       COALESCE(sub.academic_program, p.academic_program, 'SNPMB/UTBK') as academic_program,
                       sub.status, sub.expires_at, sub.started_at
                FROM subscriptions sub
                LEFT JOIN plans p ON sub.plan_id = p.id
                WHERE (sub.student_id = ? OR sub.student_id = ?) 
                  AND sub.status = 'active' 
                  AND (sub.expires_at IS NULL OR sub.expires_at > NOW())
                ORDER BY sub.expires_at DESC
            ");
            $stmtSubs->execute([$user['id'], $payload->user_id ?? $user['id']]);
            $active_subs = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($active_subs)) {
                $user['is_premium'] = true;
                if (!empty($active_subs[0]['plan_name_snapshot'])) {
                    $user['plan_name'] = $active_subs[0]['plan_name_snapshot'];
                }
            }
        } catch (\Throwable $e) {}
        $user['active_subscriptions'] = $active_subs;

        $plan_labels = [
            'mandiri' => 'Paket Mandiri',
            'starter' => 'Paket Mandiri',
            'utama'   => 'Paket Pejuang PTN (Utama)',
            'pejuang' => 'Paket Pejuang PTN (Utama)',
            'vip'     => 'Paket Elite Mentoring (VIP)',
            'elite'   => 'Paket Elite Mentoring (VIP)',
            'free'    => 'Paket Free / Trial (' . $user['academic_program'] . ')'
        ];
        $user['plan_name'] = $plan_labels[$plan] ?? ucfirst($plan);
        if ($user['is_premium'] && (str_contains($user['plan_name'], 'Free') || str_contains($user['plan_name'], 'Trial'))) {
            $user['plan_name'] = !empty($active_subs[0]['plan_name_snapshot']) ? $active_subs[0]['plan_name_snapshot'] : 'Paket Mandiri';
        }

        // Header cache hint untuk CDN / reverse proxy (jika ada)
        // Browser tidak meng-cache karena credentials: include
        header('Cache-Control: private, max-age=30');

        echo json_encode(["user" => $user]);
        exit;
    } else {
        // Fallback auto-heal if user exists in users table
        try {
            $stmtFallback = $pdo->prepare("SELECT id, identity_key, COALESCE(name, '') as name FROM users WHERE id = ? OR LOWER(TRIM(identity_key)) = LOWER(TRIM(?)) LIMIT 1");
            $stmtFallback->execute([$payload->user_id ?? $payload->id, $payload->email ?? '']);
            $fb = $stmtFallback->fetch(PDO::FETCH_ASSOC);
            if ($fb) {
                $newStdId = $payload->id ?: ('std_' . bin2hex(random_bytes(8)));
                $stdName = !empty(trim($fb['name'])) ? trim($fb['name']) : explode('@', $fb['identity_key'])[0];
                $pdo->prepare("INSERT INTO students (id, name, email, plan, tenant_id) VALUES (?, ?, ?, 'free', 'default_tenant') ON DUPLICATE KEY UPDATE name=VALUES(name)")
                    ->execute([$newStdId, $stdName, $fb['identity_key']]);
                
                header('Cache-Control: private, max-age=30');
                echo json_encode([
                    "user" => [
                        'id' => $newStdId,
                        'name' => $stdName,
                        'email' => $fb['identity_key'],
                        'role' => 'student',
                        'plan' => 'free',
                        'plan_name' => 'Paket Free / Trial',
                        'is_premium' => false,
                        'tenant_id' => $payload->tenant_id ?? 'default_tenant'
                    ]
                ]);
                exit;
            }
        } catch (\Throwable $e) {}

        http_response_code(404);
        echo json_encode(["error" => "User tidak ditemukan"]);
        exit;
    }
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint tidak ditemukan"]);
}
