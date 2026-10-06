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
        $stmt_aff = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND tenant_id = ?");
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

        $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $tenant_id, $referred_by]);

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

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["error" => "Email dan password harus diisi"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, COALESCE(s.id, a.id, ur.reference_id) as student_id, COALESCE(s.name, a.name, 'Admin') as name, COALESCE(s.email, a.username, u.identity_key) as email, COALESCE(s.target_ptn, 'UI') as target_ptn, COALESCE(t.is_active, 1) as tenant_active
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN students s ON (ur.reference_id = s.id OR u.identity_key = s.email)
        LEFT JOIN admins a ON (ur.reference_id = a.id OR u.identity_key = a.username)
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE u.identity_key = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $password_matched = false;
    if ($user) {
        $stored_hash = $user['password'] ?? '';
        if (password_verify($password, $stored_hash) || $stored_hash === $password || $stored_hash === md5($password)) {
            $password_matched = true;
            // Auto-upgrade to bcrypt if plain text or md5 or needs rehash
            if ($stored_hash === $password || $stored_hash === md5($password) || password_needs_rehash($stored_hash, PASSWORD_BCRYPT)) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['user_id']]);
                } catch (\Exception $e) {}
            }
        }
    }

    if ($user && $password_matched) {
        if ((int)$user['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }
        reset_rate_limit($pdo, 'login');
        
        // Update last login if student
        if ($user['student_id']) {
            $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?")->execute([$user['student_id']]);
        }
        
        $token = generate_jwt(['user_id' => $user['user_id'], 'id' => $user['reference_id'], 'role' => $user['role'], 'tenant_id' => $user['tenant_id']]);
        
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

        unset($user['password']);
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
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $payload->id]);
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
        $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $email, $hashed_password, $payload->id]);

        $stmt = $pdo->prepare("UPDATE users SET identity_key = ?, password = ? WHERE id = ?");
        $stmt->execute([$email, $hashed_password, $payload->user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $payload->id]);

        $stmt = $pdo->prepare("UPDATE users SET identity_key = ? WHERE id = ?");
        $stmt->execute([$email, $payload->user_id]);
    }

    echo json_encode(["success" => true, "message" => "Profil berhasil diperbarui"]);
    exit;
}
elseif ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = authenticate();

    // Admin / Superadmin: ambil data dari tabel admins
    if ($payload->role === 'admin' || $payload->role === 'superadmin') {
        $stmt = $pdo->prepare("SELECT id, username, name FROM admins WHERE id = ?");
        $stmt->execute([$payload->id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $user = [
                'id'         => $admin['id'],
                'name'       => $admin['name'] ?? $admin['username'] ?? 'Admin',
                'email'      => $admin['username'] ?? '',
                'role'       => $payload->role,
                'is_premium' => true,
                'tenant_id'  => $payload->tenant_id,
            ];
            header('Cache-Control: private, max-age=30');
            echo json_encode(["user" => $user]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Admin tidak ditemukan"]);
        }
        exit;
    }

    // Student: query biasa + subscription check
    // Optimasi: gabungkan student data + subscription check dalam SATU query
    // menggunakan subquery EXISTS untuk menghindari round-trip kedua ke DB
    $stmt = $pdo->prepare("
        SELECT s.*,
               (SELECT COUNT(*) > 0
                FROM subscriptions sub
                WHERE sub.student_id = s.id
                  AND sub.status = 'active'
                  AND sub.expires_at > NOW()
                LIMIT 1) AS is_premium
        FROM students s
        WHERE s.id = ?
    ");
    $stmt->execute([$payload->id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        unset($user['password']);
        $user['is_premium']  = (bool)$user['is_premium'];
        $user['role']        = 'student';
        $user['tenant_id']   = $payload->tenant_id;

        // Header cache hint untuk CDN / reverse proxy (jika ada)
        // Browser tidak meng-cache karena credentials: include
        header('Cache-Control: private, max-age=30');

        echo json_encode(["user" => $user]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "User tidak ditemukan"]);
    }
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint tidak ditemukan"]);
}
?>
