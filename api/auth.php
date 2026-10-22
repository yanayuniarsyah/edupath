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

    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $target_ptn = trim($input['target_ptn'] ?? '');
    $referral_code = trim($input['referral_code'] ?? '');

    if (!$name || !$email || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Semua field harus diisi"]);
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

    // Resolve Affiliate Attribution
    $referred_by = null;
    if (!empty($referral_code)) {
        // Find affiliate by referral code AND ensure it matches the B2C tenant_id context
        $stmt_aff = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND (tenant_id = ? OR tenant_id IS NULL OR tenant_id = '')");
        $stmt_aff->execute([$referral_code, $tenant_id]);
        $aff = $stmt_aff->fetch();
        if ($aff) {
            $referred_by = $aff['id'];
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
            $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, school, school_id, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $school ?: null, $school_id ?: null, $tenant_id, $referred_by]);
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, school, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $school ?: null, $tenant_id, $referred_by]);
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
            "user"          => ["id" => $id, "name" => $name, "email" => $email, "target_ptn" => $target_ptn]
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

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Auto-reset rate limit for test/demo accounts
    if (in_array(strtolower($email), ['bk.smansaciamis@edupath.co.id', 'student@uat.edupath.local', 'superadmin@uat.edupath.local', 'tutor@uat.edupath.local', 'admin@uat.edupath.local'])) {
        reset_rate_limit($pdo, 'login');
        reset_rate_limit($pdo, 'school_login');
    }

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

    try {
        // Auto-heal UAT student account if credentials match
    if ($email === 'student@uat.edupath.local' && $password === 'EduPathStudent01!2026') {
        try {
            $uId = 'uat-u-student';
            $stuId = 'uat-r-student';
            $hPass = password_hash('EduPathStudent01!2026', PASSWORD_BCRYPT);
            $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$uId', '$email', '$hPass', 1) ON DUPLICATE KEY UPDATE password='$hPass', is_active=1");
            $pdo->exec("INSERT INTO students (id, name, email, password, tenant_id, is_active) VALUES ('$stuId', 'UAT Student', '$email', '$hPass', 'uat-tenant-01', 1) ON DUPLICATE KEY UPDATE password='$hPass', is_active=1");
            $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('ur-uat-stu', '$uId', 'uat-tenant-01', 'student', '$stuId') ON DUPLICATE KEY UPDATE role='student'");
            reset_rate_limit($pdo, 'login');
        } catch (\Throwable $e) {}
    }

    // Ensure schools table exists for join and auto-heal
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

    // Auto-heal Demo / UAT School BK account (SMANSA Ciamis)
    if (in_array(strtolower($email), ['bk.smansaciamis@edupath.co.id', 'bk@smansaciamis.sch.id', 'smansaciamis']) && ($password === 'EduPathSchool01!2026' || $password === 'smansa123')) {
        try {
            $uId = 'uat-u-school-smansa';
            $schId = 'uat-r-school-smansa';
            $hPass = password_hash('EduPathSchool01!2026', PASSWORD_BCRYPT);
            $schName = 'SMANSA Ciamis';
            $pdo->exec("INSERT INTO schools (id, name, slug, npsn, city, bk_name, bk_phone, bk_email, estimated_students) 
                        VALUES ('$schId', '$schName', 'smansa-ciamis', '20211567', 'Kabupaten Ciamis', 'Dra. H. Nuraeni, M.Pd', '081223344556', 'bk.smansaciamis@edupath.co.id', 350) 
                        ON DUPLICATE KEY UPDATE name='$schName', bk_name='Dra. H. Nuraeni, M.Pd', bk_email='bk.smansaciamis@edupath.co.id'");
            $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) 
                        VALUES ('$uId', '$email', '$hPass', 1) 
                        ON DUPLICATE KEY UPDATE password='$hPass', is_active=1");
            $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) 
                        VALUES ('ur-uat-sch-smansa', '$uId', 'default_tenant', 'school_bk', '$schId') 
                        ON DUPLICATE KEY UPDATE role='school_bk', reference_id='$schId', tenant_id='default_tenant'");
            reset_rate_limit($pdo, 'login');
            reset_rate_limit($pdo, 'school_login');
        } catch (\Throwable $e) {}
    }

    // Ensure affiliates columns exist for backward-compatibility
    try {
        $affCols = $pdo->query("DESCRIBE affiliates")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('name', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN name VARCHAR(150) NULL");
        if (!in_array('email', $affCols)) $pdo->exec("ALTER TABLE affiliates ADD COLUMN email VARCHAR(150) NULL");
    } catch (\Throwable $e) {}

    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password as user_password, s.password as student_password, ur.role, ur.tenant_id, ur.reference_id, 
               COALESCE(s.id, a.id, sch.id, ur.reference_id) as student_id, 
               COALESCE(NULLIF(TRIM(s.name), ''), NULLIF(TRIM(aff.name), ''), NULLIF(TRIM(aff.bank_owner), ''), NULLIF(TRIM(a.name), ''), NULLIF(TRIM(sch.name), ''), 'Pengguna EduPath') as name, 
               COALESCE(s.email, aff.email, a.username, sch.bk_email, u.identity_key) as email, 
               s.school as student_school,
               s.phone as student_phone,
               COALESCE(s.target_ptn, '') as target_ptn, 
               COALESCE(s.plan, 'free') as plan,
               COALESCE(t.is_active, 1) as tenant_active,
               COALESCE(aff.referral_code, '') as referral_code,
               COALESCE(s.school_id, sch.id) as school_id,
               sch.name as school_name,
               sch.bk_name,
               sch.city as school_city,
               sch.slug as school_slug
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN students s ON (ur.reference_id = s.id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(s.email)))
        LEFT JOIN admins a ON (ur.reference_id = a.id OR u.identity_key = a.username)
        LEFT JOIN affiliates aff ON (ur.reference_id = aff.id OR u.id = aff.user_id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(aff.email)))
        LEFT JOIN schools sch ON (ur.reference_id = sch.id OR LOWER(TRIM(u.identity_key)) = LOWER(TRIM(sch.bk_email)))
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE (LOWER(TRIM(u.identity_key)) = LOWER(TRIM(?)) OR (s.email IS NOT NULL AND LOWER(TRIM(s.email)) = LOWER(TRIM(?))) OR (aff.email IS NOT NULL AND LOWER(TRIM(aff.email)) = LOWER(TRIM(?))) OR (a.username IS NOT NULL AND LOWER(TRIM(a.username)) = LOWER(TRIM(?)))) 
          AND u.is_active = 1
        ORDER BY CASE ur.role 
            WHEN 'superadmin' THEN 1 
            WHEN 'admin' THEN 2 
            WHEN 'school_bk' THEN 3 
            WHEN 'affiliate' THEN 4 
            WHEN 'teacher' THEN 5 
            ELSE 6 
        END ASC
        LIMIT 1
    ");
    $stmt->execute([$email, $email, $email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $password_matched = false;
    if ($user) {
        $candidates = array_filter([$user['user_password'] ?? '', $user['student_password'] ?? '']);
        foreach ($candidates as $stored_hash) {
            if (password_verify($password, $stored_hash) || $stored_hash === $password || $stored_hash === md5($password)) {
                $password_matched = true;
                // Auto-upgrade / sync hash into both tables
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['user_id']]);
                    if (!empty($user['student_id']) && $user['role'] === 'student') {
                        $pdo->prepare("UPDATE students SET password = ? WHERE id = ?")->execute([$new_hash, $user['student_id']]);
                    }
                } catch (\Throwable $e) {}
                break;
            }
        }
    }

    if ($user && $password_matched) {
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            http_response_code(403);
            echo json_encode(["error" => "Akun Administrator & Superadmin wajib masuk melalui portal admin di /#/admin."]);
            exit;
        }

        if (!in_array($user['role'], ['student', 'school_bk', 'teacher', 'tutor', 'affiliate'])) {
            http_response_code(403);
            echo json_encode(["error" => "Peran akun tidak dikenali."]);
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
        $user_email = $user['student_email'] ?? $user['identity_key'];
        if ($user['role'] === 'student') {
            $ref_id = $user['student_id'] ?? $user['reference_id'] ?? $user['user_id'];
        } elseif ($user['role'] === 'affiliate') {
            $ref_id = $user['affiliate_id'] ?? $user['reference_id'] ?? $user['user_id'];
        }

        $token_payload = [
            'user_id'   => $user['user_id'], 
            'id'        => $ref_id, 
            'role'      => $user['role'], 
            'tenant_id' => $user['tenant_id'],
            'email'     => $user_email
        ];

        if ($user['role'] === 'school_bk') {
            $token_payload['school_id'] = $user['school_id'] ?? $user['reference_id'];
            $token_payload['school_name'] = $user['school_name'] ?? $user['name'];
            $user['school'] = [
                'id' => $user['school_id'] ?? $user['reference_id'],
                'name' => $user['school_name'] ?? $user['name'],
                'slug' => $user['school_slug'] ?? '',
                'bk_name' => $user['bk_name'] ?? 'Koordinator BK',
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
        if ($user['role'] === 'student') {
            $user['school'] = $user['student_school'] ?? '';
            $user['phone'] = $user['student_phone'] ?? '';
        } elseif ($user['role'] === 'affiliate') {
            $user['id'] = $ref_id;
            $user['name'] = $user['affiliate_name'] ?? $user['identity_key'];
            $user['referral_code'] = $user['affiliate_code'] ?? '';
            $user['is_premium'] = true;
            $user['plan'] = 'affiliate';
            $user['plan_name'] = 'Mitra Afiliasi EduPath';
        }
        $plan = strtolower($user['plan'] ?? 'free');
        $is_paid_plan = false;
        $paid_keywords = ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium'];
        foreach ($paid_keywords as $kw) {
            if (str_contains($plan, $kw)) {
                $is_paid_plan = true;
                break;
            }
        }
        $user['is_premium'] = ($user['role'] === 'affiliate' || $user['role'] === 'school_bk') ? true : $is_paid_plan;
        $plan_labels = [
            'mandiri' => 'Paket Mandiri',
            'starter' => 'Paket Mandiri',
            'utama'   => 'Paket Pejuang PTN (Utama)',
            'pejuang' => 'Paket Pejuang PTN (Utama)',
            'vip'     => 'Paket Elite Mentoring (VIP)',
            'elite'   => 'Paket Elite Mentoring (VIP)',
            'free'    => 'Paket Free / Trial'
        ];
        $user['plan_name'] = $plan_labels[$plan] ?? ucfirst($plan);

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
    echo json_encode(["error" => "Terjadi kendala saat login: " . $e->getMessage()]);
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
    $payload = authenticate();
    $refresh_token = $input['refresh_token'] ?? '';
    
    if (!empty($refresh_token)) {
        $rt_hash = hash('sha256', $refresh_token);
        try {
            // Revoke specific refresh token session
            $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW() WHERE token_hash = ? AND student_id = ?")
                ->execute([$rt_hash, $payload->id]);
        } catch (PDOException $e) {}
    }
    
    clear_auth_cookies();
    echo json_encode(["success" => true]);
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

    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(["error" => "Nama dan email wajib diisi"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Format email tidak valid"]);
        exit;
    }

    // Cek email dipakai akun lain
    $isStaffRole = in_array($payload->role, ['admin', 'superadmin', 'teacher', 'guru', 'tutor']);
    if ($isStaffRole) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt->execute([$email, $payload->id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $stmt->execute([$email, $payload->id]);
    }
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Email sudah digunakan oleh akun lain"]);
        exit;
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "Password minimal 6 karakter"]);
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $school = trim($input['school'] ?? '');
        $school_id = trim($input['school_id'] ?? '');
        if (!empty($school) && empty($school_id)) {
            try {
                $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $stmt_sch->execute([$school]);
                $found_id = $stmt_sch->fetchColumn();
                if ($found_id) $school_id = $found_id;
            } catch (\Throwable $e) {}
        }

        if ($isStaffRole) {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hashed_password, $payload->id]);
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, password = ?, school = ?, school_id = ? WHERE id = ? OR id = ? OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $hashed_password, $school ?: null, $school_id ?: null, $payload->id, $payload->user_id ?? '', $payload->email ?? '']);
            } catch (\Throwable $e) {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, password = ? WHERE id = ? OR id = ? OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $hashed_password, $payload->id, $payload->user_id ?? '', $payload->email ?? '']);
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET identity_key = ?, password = ? WHERE id = ?");
        $stmt->execute([$email, $hashed_password, $payload->user_id]);
        try {
            $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('name', $userCols)) {
                $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $payload->user_id]);
            }
        } catch (\Throwable $e) {}
    } else {
        $school = trim($input['school'] ?? '');
        $school_id = trim($input['school_id'] ?? '');
        if (!empty($school) && empty($school_id)) {
            try {
                $stmt_sch = $pdo->prepare("SELECT id FROM schools WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $stmt_sch->execute([$school]);
                $found_id = $stmt_sch->fetchColumn();
                if ($found_id) $school_id = $found_id;
            } catch (\Throwable $e) {}
        }

        if ($isStaffRole) {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ? WHERE id = ?");
            $stmt->execute([$name, $email, $payload->id]);
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, school = ?, school_id = ? WHERE id = ? OR id = ? OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $school ?: null, $school_id ?: null, $payload->id, $payload->user_id ?? '', $payload->email ?? '']);
            } catch (\Throwable $e) {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE id = ? OR id = ? OR LOWER(email) = LOWER(?)");
                $stmt->execute([$name, $email, $payload->id, $payload->user_id ?? '', $payload->email ?? '']);
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET identity_key = ? WHERE id = ?");
        $stmt->execute([$email, $payload->user_id]);
        try {
            $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('name', $userCols)) {
                $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $payload->user_id]);
            }
        } catch (\Throwable $e) {}
    }

    echo json_encode(["success" => true, "message" => "Profil berhasil diperbarui"]);
    exit;
}
elseif ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = authenticate();

    // School BK Coordinator
    if ($payload->role === 'school_bk') {
        $schId = $payload->school_id ?? $payload->reference_id ?? $payload->id;
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ? OR LOWER(bk_email) = LOWER(?) LIMIT 1");
        $stmt->execute([$schId, $payload->email ?? '']);
        $sch = $stmt->fetch(PDO::FETCH_ASSOC);
        $bkName = $sch['bk_name'] ?? $sch['bk_coordinator_name'] ?? $sch['name'] ?? 'Koordinator BK';
        $bkEmail = $sch['bk_email'] ?? $sch['bk_coordinator_email'] ?? ($payload->email ?? '');
        $bkPhone = $sch['bk_phone'] ?? $sch['bk_coordinator_phone'] ?? '';
        $user = [
            'id' => $payload->id,
            'name' => $bkName,
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
            SELECT u.id as user_id, u.identity_key, ur.role, ur.tenant_id, a.id as admin_id, a.username, 
                   COALESCE(a.name, t.name, u.identity_key) as name,
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
        $stmt = $pdo->prepare("SELECT * FROM affiliates WHERE id = ? OR user_id = ? OR LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->execute([$payload->id, $payload->user_id ?? '', $payload->email ?? '']);
        $aff = $stmt->fetch(PDO::FETCH_ASSOC);

        $user = [
            'id'            => $aff['id'] ?? $payload->id,
            'name'          => $aff['name'] ?? $aff['bank_owner'] ?? 'Mitra Afiliasi',
            'email'         => $aff['email'] ?? ($payload->email ?? ''),
            'role'          => 'affiliate',
            'referral_code' => $aff['referral_code'] ?? '',
            'bank_name'     => $aff['bank_name'] ?? '',
            'bank_account'  => $aff['bank_account'] ?? '',
            'bank_owner'    => $aff['bank_owner'] ?? '',
            'is_premium'    => true,
            'plan'          => 'affiliate',
            'plan_name'     => 'Mitra Afiliasi EduPath',
            'tenant_id'     => $payload->tenant_id ?? 'default_tenant'
        ];
        header('Cache-Control: private, max-age=30');
        echo json_encode(["user" => $user]);
        exit;
    }

    // Student: query biasa + subscription check
    $stmt = $pdo->prepare("
        SELECT s.*,
               (SELECT COUNT(*) > 0
                FROM subscriptions sub
                WHERE sub.student_id = s.id
                  AND sub.status = 'active'
                  AND sub.expires_at > NOW()
                LIMIT 1) AS has_sub
        FROM students s
        WHERE s.id = ?
           OR s.id = ?
           OR (s.email IS NOT NULL AND LOWER(TRIM(s.email)) = LOWER(TRIM(?)))
        LIMIT 1
    ");
    $stmt->execute([$payload->id, $payload->user_id ?? '', $payload->email ?? '']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        unset($user['password']);
        if (empty(trim($user['name'] ?? '')) && !empty($payload->user_id)) {
            try {
                $stmtU = $pdo->prepare("SELECT name FROM users WHERE id = ?");
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
        $paid_keywords = ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium'];
        foreach ($paid_keywords as $kw) {
            if (str_contains($plan, $kw)) {
                $is_paid_plan = true;
                break;
            }
        }
        $user['is_premium']  = $is_paid_plan || (bool)($user['has_sub'] ?? false);
        $user['role']        = 'student';
        $user['tenant_id']   = $payload->tenant_id ?? ($user['tenant_id'] ?? 'default_tenant');
        $user['name']        = !empty(trim($user['name'] ?? '')) ? trim($user['name']) : ($payload->email ?? 'Siswa EduPath');
        $user['school']      = $user['school'] ?? '';
        $user['school_id']   = $user['school_id'] ?? null;
        $user['target_ptn']  = $user['target_ptn'] ?? '';
        $user['phone']       = $user['phone'] ?? '';
        $plan_labels = [
            'mandiri' => 'Paket Mandiri',
            'starter' => 'Paket Mandiri',
            'utama'   => 'Paket Pejuang PTN (Utama)',
            'pejuang' => 'Paket Pejuang PTN (Utama)',
            'vip'     => 'Paket Elite Mentoring (VIP)',
            'elite'   => 'Paket Elite Mentoring (VIP)',
            'free'    => 'Paket Free / Trial'
        ];
        $user['plan_name'] = $plan_labels[$plan] ?? ucfirst($plan);

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
?>
