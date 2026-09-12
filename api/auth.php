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
    $tenant_id = '553af312-fb50-4e24-93ee-0d1abd52a62d'; // B2C Legacy Tenant
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

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $email, $hashed_password]);

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
        echo json_encode(["error" => "Gagal mendaftar"]);
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
        SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, s.id as student_id, t.is_active as tenant_active
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN students s ON ur.reference_id = s.id
        JOIN tenants t ON ur.tenant_id = t.id
        WHERE u.identity_key = ? AND u.is_active = 1 AND ur.role = 'student'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ((int)$user['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }
        reset_rate_limit($pdo, 'login');
        
        // Update last login
        $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?")->execute([$user['reference_id']]);
        
        $token = generate_jwt(['user_id' => $user['user_id'], 'id' => $user['reference_id'], 'role' => 'student', 'tenant_id' => $user['tenant_id']]);
        
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
elseif ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = authenticate();
    if ($payload->role !== 'student') {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$payload->id]);
    $user = $stmt->fetch();

    if ($user) {
        unset($user['password']);
        echo json_encode(["user" => $user]); // Standardized response
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
