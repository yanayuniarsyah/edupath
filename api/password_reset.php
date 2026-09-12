<?php
// api/password_reset.php
// Password Reset Flow — Production Grade
// Endpoint: /api/password_reset.php?action=forgot | action=reset
//
// SECURITY NOTES:
// - Raw token TIDAK disimpan di database, hanya SHA-256 hash-nya
// - Token single-use: dimark used_at setelah digunakan
// - Token expired setelah 15 menit
// - Generic response untuk mencegah user enumeration
// - Rate limiting pada forgot password
// - Tidak ada debug info di production

require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// ----------------------------------------------------------------
// HELPER: Send reset email
// Menggunakan mail() PHP (SMTP dikonfigurasi di server/cPanel)
// Untuk SMTP custom, ganti implementasi ini di Phase berikutnya
// ----------------------------------------------------------------
function send_reset_email(string $to_email, string $to_name, string $raw_token): bool {
    $from_name  = env('MAIL_FROM_NAME', 'EduPath');
    $from_email = env('MAIL_FROM', 'noreply@edupath.co.id');
    $subject    = '[EduPath] Permintaan Reset Password';

    // Format token yang mudah dibaca (tambahkan tanda hubung tiap 4 karakter)
    $formatted_token = implode('-', str_split($raw_token, 4));

    $body = "Halo {$to_name},\n\n"
        . "Kami menerima permintaan reset password untuk akun EduPath Anda.\n\n"
        . "Kode reset password Anda adalah:\n\n"
        . "  {$formatted_token}\n\n"
        . "Kode ini berlaku selama 15 menit dan hanya dapat digunakan sekali.\n\n"
        . "Jika Anda tidak meminta reset password, abaikan email ini.\n"
        . "Password Anda TIDAK akan berubah.\n\n"
        . "Salam,\nTim EduPath";

    $headers = implode("\r\n", [
        "From: {$from_name} <{$from_email}>",
        "Reply-To: {$from_email}",
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "X-Mailer: EduPath-PHP",
    ]);

    return mail($to_email, $subject, $body, $headers);
}

// ----------------------------------------------------------------
// ACTION: forgot_password
// ----------------------------------------------------------------
if ($action === 'forgot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: max 3 permintaan per 15 menit per IP
    if (!check_rate_limit($pdo, 'forgot_password', 3, 15)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.']);
        exit;
    }

    $email = strtolower(trim($input['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
        exit;
    }

    // GENERIC RESPONSE — jangan beri tahu apakah email terdaftar atau tidak
    // Ini mencegah user enumeration attack
    $generic_response = [
        'success' => true,
        'message' => 'Jika email tersebut terdaftar di EduPath, kode reset akan dikirimkan. Periksa inbox dan folder spam Anda.'
    ];

    // Cek apakah email terdaftar (kita asumsikan email = identity_key untuk student)
    $stmt = $pdo->prepare("
        SELECT u.id, s.name, u.identity_key as email 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN students s ON ur.reference_id = s.id 
        WHERE u.identity_key = ? AND u.is_active = 1 AND ur.role = 'student'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Email tidak terdaftar, tapi tetap return generic response
        // Tambahkan delay untuk mencegah timing attack
        usleep(random_int(200000, 400000));
        echo json_encode($generic_response);
        exit;
    }

    // Hapus token reset lama yang belum digunakan untuk email ini
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ? AND used_at IS NULL")
        ->execute([$email]);

    // Generate raw token (16 bytes = 32 hex chars)
    $raw_token  = bin2hex(random_bytes(16));
    $token_hash = hash('sha256', $raw_token);
    $token_id   = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', time() + 900); // 15 menit

    try {
        $pdo->prepare("INSERT INTO password_reset_tokens (id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)")
            ->execute([$token_id, $email, $token_hash, $expires_at]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server. Coba lagi nanti.']);
        exit;
    }

    // Kirim email
    $sent = send_reset_email($user['email'], $user['name'], $raw_token);

    if (!$sent) {
        // Email gagal terkirim — hapus token yang sudah dibuat
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE id = ?")
            ->execute([$token_id]);

        $is_dev = (defined('APP_ENV') && APP_ENV === 'development');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengirim email. Coba lagi atau hubungi admin.',
            // Di development: tampilkan token untuk testing tanpa email
            'debug_token' => $is_dev ? $raw_token : null
        ]);
        exit;
    }

    echo json_encode($generic_response);
    exit;
}

// ----------------------------------------------------------------
// ACTION: reset_password
// ----------------------------------------------------------------
elseif ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: max 5 percobaan per 15 menit
    if (!check_rate_limit($pdo, 'reset_password', 5, 15)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi nanti.']);
        exit;
    }

    $email     = strtolower(trim($input['email'] ?? ''));
    $raw_token = trim($input['token'] ?? '');
    $new_pass  = $input['new_password'] ?? '';

    if (!$email || !$raw_token || !$new_pass) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email, token, dan password baru wajib diisi.']);
        exit;
    }

    if (strlen($new_pass) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 8 karakter.']);
        exit;
    }

    // Normalisasi token: hapus tanda hubung jika user mengetik format xxx-xxxx
    $raw_token = str_replace(['-', ' '], '', $raw_token);
    $token_hash = hash('sha256', $raw_token);

    // Cari token yang valid (belum digunakan, belum expired, email cocok)
    $stmt = $pdo->prepare("
        SELECT id FROM password_reset_tokens
        WHERE token_hash = ?
          AND email = ?
          AND used_at IS NULL
          AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token_hash, $email]);
    $reset_record = $stmt->fetch();

    if (!$reset_record) {
        // Token tidak valid, expired, atau sudah digunakan
        // Generic message untuk mencegah enumeration
        usleep(random_int(100000, 300000));
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa. Silakan minta reset ulang.']);
        exit;
    }

    $hashed_new_pass = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $pdo->beginTransaction();

        // Update password user
        $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE identity_key = ?")
            ->execute([$hashed_new_pass, $email]);

        // Tandai token sebagai sudah digunakan (single-use)
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?")
            ->execute([$reset_record['id']]);

        // Revoke semua refresh token aktif user ini (force re-login)
        $stmt_student = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt_student->execute([$email]);
        $student = $stmt_student->fetch();
        if ($student) {
            $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW() WHERE student_id = ? AND revoked_at IS NULL")
                ->execute([$student['id']]);
        }

        $pdo->commit();
        reset_rate_limit($pdo, 'reset_password');

        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah. Silakan login dengan password baru Anda.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
    }
    exit;
}

else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint tidak ditemukan.']);
}
?>
