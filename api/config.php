<?php
// api/config.php
// Konfigurasi utama backend EDUPath
// JANGAN masukkan secret asli ke file ini.
// Semua secret dibaca dari environment variable (file .env atau server config).

require_once __DIR__ . '/env.php';

// --- Load .env ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

// Gunakan file api/.env terlebih dahulu (spesifik backend), lalu fallback ke root .env
load_env(__DIR__ . '/.env');
load_env(dirname(__DIR__) . '/.env');
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    load_env($_SERVER['DOCUMENT_ROOT'] . '/.env');
    load_env($_SERVER['DOCUMENT_ROOT'] . '/api/.env');
}
load_env(dirname(dirname(__DIR__)) . '/.env');

// ----------------------------------------------------------------
// CORS HARDENING — Strict origin validation
// ----------------------------------------------------------------
$allowed_origins_raw = env('ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8000,http://127.0.0.1:5173,http://127.0.0.1:8000');
$allowed_origins     = array_filter(array_map('trim', explode(',', $allowed_origins_raw)));
$request_origin      = trim($_SERVER['HTTP_ORIGIN'] ?? '');

$is_origin_allowed = false;
if ($request_origin !== '') {
    $origin_parts  = parse_url($request_origin);
    $origin_scheme = strtolower($origin_parts['scheme'] ?? '');
    $origin_host   = strtolower($origin_parts['host'] ?? '');

    if (in_array($request_origin, $allowed_origins, true)) {
        $is_origin_allowed = true;
    } elseif (in_array($origin_scheme, ['http', 'https'], true)) {
        if ($origin_host === 'edupath.co.id' || str_ends_with($origin_host, '.edupath.co.id') ||
            $origin_host === 'elyana.biz.id' || str_ends_with($origin_host, '.elyana.biz.id') ||
            $origin_host === 'localhost' || $origin_host === '127.0.0.1') {
            $is_origin_allowed = true;
        }
    }
}

if ($is_origin_allowed) {
    header("Access-Control-Allow-Origin: $request_origin");
    header("Access-Control-Allow-Credentials: true");
    header('Vary: Origin');
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With");

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// ----------------------------------------------------------------
// DATABASE — Dari environment variable
// ----------------------------------------------------------------
$host    = env('DB_HOST', 'localhost');
$db_user = env('DB_USER');
$db_pass = env('DB_PASSWORD');
$db_name = env('DB_NAME');
$port    = env('DB_PORT', '3306');

// Validasi: pastikan konfigurasi database ada
if (empty($db_user) || empty($db_name)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Konfigurasi server tidak lengkap: DB_USER atau DB_NAME belum terbaca di .env cPanel.",
        "status" => "error",
        "debug_paths" => [
            "api_dir" => __DIR__,
            "root_dir" => dirname(__DIR__),
            "api_env_exists" => file_exists(__DIR__ . '/.env'),
            "root_env_exists" => file_exists(dirname(__DIR__) . '/.env')
        ]
    ]);
    exit;
}

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 2 // 2s timeout prevents LiteSpeed 503 worker crash
];

// On Linux/cPanel environments, MySQL is accessed via UNIX socket ('localhost')
// 127.0.0.1 forces TCP which is blocked/disabled on cPanel, causing hung processes and LiteSpeed 503
if (PHP_OS_FAMILY !== 'Windows' && $host === '127.0.0.1') {
    $primaryHost  = 'localhost';
    $fallbackHost = '127.0.0.1';
} else {
    $primaryHost  = $host;
    $fallbackHost = ($host === 'localhost') ? '127.0.0.1' : 'localhost';
}

try {
    $dsn = "mysql:host=$primaryHost;dbname=$db_name;charset=utf8mb4";
    if ($primaryHost !== 'localhost' && !empty($port)) {
        $dsn .= ";port=$port";
    }
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    try {
        $dsnFallback = "mysql:host=$fallbackHost;dbname=$db_name;charset=utf8mb4";
        if ($fallbackHost !== 'localhost' && !empty($port)) {
            $dsnFallback .= ";port=$port";
        }
        $pdo = new PDO($dsnFallback, $db_user, $db_pass, $pdo_options);
    } catch (PDOException $eFallback) {
        http_response_code(500);
        $errResponse = [
            "success" => false,
            "error" => "Koneksi database gagal. Silakan hubungi administrator sistem."
        ];
        if (env('APP_ENV') === 'development' || env('APP_DEBUG') === 'true') {
            $errResponse["detail"] = $eFallback->getMessage();
            $errResponse["host_tried"] = $primaryHost;
            $errResponse["fallback_tried"] = $fallbackHost;
        }
        echo json_encode($errResponse);
        exit;
    }
}

try {
    $pdo->exec("SET time_zone = '+07:00'");
} catch (\Throwable $e) {}

// ----------------------------------------------------------------
// AUTO-HEAL CORE SCHEMA (Ensures critical tables & columns exist)
// ----------------------------------------------------------------
function ensure_core_schema_sync($pdo) {
    static $synced = false;
    if ($synced) return;

    $lockFile = sys_get_temp_dir() . '/edupath_core_schema_' . md5(__DIR__) . '.lock';
    // Hanya eksekusi DDL jika lockfile belum ada, sudah lewat 24 jam, atau dipaksa via ?sync_schema=1
    if (!isset($_GET['sync_schema']) && file_exists($lockFile) && (time() - @filemtime($lockFile) < 86400)) {
        $synced = true;
        return;
    }

    $synced = true;
    @touch($lockFile);

    try {
        // 1. users table & display_name
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                identity_key VARCHAR(150) NOT NULL UNIQUE,
                display_name VARCHAR(150) NULL,
                password VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('display_name', $userCols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(150) NULL AFTER identity_key");
        }
    } catch (\Throwable $e) {}

    try {
        // 2. tenants table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tenants (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 3. user_roles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NULL,
                role VARCHAR(50) NOT NULL,
                reference_id VARCHAR(36) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (tenant_id),
                INDEX (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 4. admins table columns
        $admCols = $pdo->query("DESCRIBE admins")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('name', $admCols)) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN name VARCHAR(100) NULL");
        }
        if (!in_array('tenant_id', $admCols)) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN tenant_id VARCHAR(36) NULL");
        }
    } catch (\Throwable $e) {}

    try {
        // 5. students table columns
        $stuCols = $pdo->query("DESCRIBE students")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('tenant_id', $stuCols)) {
            $pdo->exec("ALTER TABLE students ADD COLUMN tenant_id VARCHAR(36) NULL");
        }
        if (!in_array('school_id', $stuCols)) {
            $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL");
        }
    } catch (\Throwable $e) {}

    try {
        // 6. audit_logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                actor_id VARCHAR(36) NULL,
                tenant_id VARCHAR(36) NULL,
                action VARCHAR(100) NOT NULL,
                target_id VARCHAR(36) NULL,
                metadata JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                level VARCHAR(20) DEFAULT 'INFO',
                signature VARCHAR(64) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (actor_id),
                INDEX (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 7. academic_programs table (PRD v1.1 Unified Catalog)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS academic_programs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                target_level ENUM('SD', 'SMP', 'SMA', 'ALUMNI') NOT NULL DEFAULT 'SMA',
                subject_config JSON NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            INSERT IGNORE INTO academic_programs (id, code, name, description, target_level, status) VALUES
            ('prog-tka-sd', 'TKA_SD', 'TKA SD', 'Program Tes Kemampuan Akademik tingkat SD', 'SD', 'active'),
            ('prog-tka-smp', 'TKA_SMP', 'TKA SMP', 'Program Tes Kemampuan Akademik tingkat SMP', 'SMP', 'active'),
            ('prog-tka-sma', 'TKA_SMA', 'TKA SMA', 'Program Tes Kemampuan Akademik tingkat SMA', 'SMA', 'active'),
            ('prog-snpmb-utbk', 'SNPMB_UTBK', 'SNPMB/UTBK', 'Persiapan Seleksi Nasional Berdasarkan Tes PTN', 'SMA', 'active'),
            ('prog-snpmb-alumni', 'SNPMB_ALUMNI', 'SNPMB Alumni', 'Program intensif alumni/gap year', 'ALUMNI', 'active');
        ");
    } catch (\Throwable $e) {}

    try {
        // 8. plans catalog extensions (PRD v1.1 Unified Catalog)
        $planCols = $pdo->query("DESCRIBE plans")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('academic_program', $planCols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN academic_program VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK' AFTER tenant_id");
        }
        if (!in_array('delivery_mode', $planCols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN delivery_mode ENUM('online', 'offline', 'hybrid') NOT NULL DEFAULT 'online' AFTER academic_program");
        }
        if (!in_array('package_code', $planCols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN package_code VARCHAR(50) NULL AFTER delivery_mode");
        }
        if (!in_array('components', $planCols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN components JSON NULL AFTER features");
        }
        if (!in_array('base_price', $planCols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN base_price DECIMAL(15,2) NULL AFTER price");
            $pdo->exec("UPDATE plans SET base_price = price WHERE base_price IS NULL");
        }
    } catch (\Throwable $e) {}

    try {
        // 9. orders snapshot & upgrade audit (PRD v1.1 Phase 2)
        $orderCols = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('order_type', $orderCols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN order_type ENUM('new_purchase', 'upgrade', 'renewal') NOT NULL DEFAULT 'new_purchase' AFTER is_renewal");
        }
        if (!in_array('package_version', $orderCols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN package_version INT NOT NULL DEFAULT 1 AFTER billing_cycle_snapshot");
        }
        if (!in_array('purchase_snapshot', $orderCols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN purchase_snapshot JSON NULL AFTER package_version");
        }
        if (!in_array('upgrade_from_order_id', $orderCols)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN upgrade_from_order_id VARCHAR(36) NULL AFTER purchase_snapshot");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS upgrade_audit_logs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NULL,
                from_order_id VARCHAR(36) NULL,
                from_plan_id VARCHAR(36) NULL,
                to_order_id VARCHAR(36) NOT NULL,
                to_plan_id VARCHAR(36) NOT NULL,
                from_plan_name VARCHAR(150) NULL,
                to_plan_name VARCHAR(150) NOT NULL,
                upgrade_price_paid DECIMAL(15,2) NOT NULL,
                previous_expires_at DATETIME NULL,
                new_expires_at DATETIME NULL,
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY ual_student_fk (student_id),
                KEY ual_to_order_fk (to_order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 10. class_offerings & makeup_requests (PRD v1.1 Phase 3 Offline Class Domain)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS class_offerings (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                tenant_id VARCHAR(36) NOT NULL,
                program_id VARCHAR(36) NULL,
                academic_program VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK',
                plan_id VARCHAR(36) NULL,
                class_id VARCHAR(36) NULL,
                name VARCHAR(150) NOT NULL,
                renewal_mode ENUM('new_enrollment', 'extend_existing') NOT NULL DEFAULT 'new_enrollment',
                max_capacity INT NOT NULL DEFAULT 15,
                registration_open_at DATETIME NULL,
                registration_close_at DATETIME NULL,
                status ENUM('draft', 'open', 'closed', 'in_progress', 'completed') NOT NULL DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY co_tenant_fk (tenant_id),
                KEY co_class_fk (class_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS makeup_requests (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                origin_session_id VARCHAR(36) NOT NULL,
                target_session_id VARCHAR(36) NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'attended') NOT NULL DEFAULT 'pending',
                approved_by VARCHAR(36) NULL,
                approved_at DATETIME NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY mr_student_fk (student_id),
                KEY mr_orig_ses_fk (origin_session_id),
                KEY mr_targ_ses_fk (target_session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 11. partner_profiles & partner_assignments (PRD v1.1 Phase 4 Dual Partner Operating Models)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS partner_profiles (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                partner_type ENUM('tenant', 'scoped_operator') NOT NULL,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(50) NOT NULL UNIQUE,
                tenant_id VARCHAR(36) NULL,
                user_id VARCHAR(36) NULL,
                contact_email VARCHAR(150) NULL,
                contact_phone VARCHAR(50) NULL,
                status ENUM('active', 'suspended', 'terminated') NOT NULL DEFAULT 'active',
                revenue_share_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY pp_type_idx (partner_type),
                KEY pp_tenant_idx (tenant_id),
                KEY pp_status_idx (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS partner_assignments (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                partner_id VARCHAR(36) NOT NULL,
                operator_user_id VARCHAR(36) NULL,
                tenant_id VARCHAR(36) NULL,
                location VARCHAR(255) NULL,
                offering_id VARCHAR(36) NULL,
                class_id VARCHAR(36) NULL,
                session_id VARCHAR(36) NULL,
                start_date DATE NOT NULL,
                end_date DATE NULL,
                status ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
                revoked_at DATETIME NULL,
                revoked_by VARCHAR(36) NULL,
                revocation_reason TEXT NULL,
                created_by VARCHAR(36) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY pa_partner_idx (partner_id),
                KEY pa_operator_idx (operator_user_id),
                KEY pa_tenant_idx (tenant_id),
                KEY pa_offering_idx (offering_id),
                KEY pa_class_idx (class_id),
                KEY pa_status_idx (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS partner_assignment_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assignment_id VARCHAR(36) NOT NULL,
                action VARCHAR(50) NOT NULL,
                actor_id VARCHAR(36) NOT NULL,
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY pal_assign_idx (assignment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 12. renewal_policies & renewal_records (PRD v1.1 Phase 5 Configurable Cohort Renewal)
        $csCols = [
            "ALTER TABLE `class_students` ADD COLUMN `valid_until` DATE NULL AFTER `joined_at`",
            "ALTER TABLE `class_students` ADD COLUMN `offering_id` VARCHAR(36) NULL AFTER `valid_until`",
            "ALTER TABLE `class_students` ADD COLUMN `renewal_reference_id` VARCHAR(36) NULL AFTER `offering_id`"
        ];
        foreach ($csCols as $colSql) {
            try { $pdo->exec($colSql); } catch (\Throwable $e) {}
        }
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS renewal_policies (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                offering_id VARCHAR(36) NOT NULL,
                renewal_mode ENUM('new_enrollment', 'extend_existing') NOT NULL DEFAULT 'new_enrollment',
                renewal_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                extension_days INT NOT NULL DEFAULT 30,
                allowed_cohort_change TINYINT(1) NOT NULL DEFAULT 1,
                eligibility_rule VARCHAR(100) NOT NULL DEFAULT 'active_or_grace',
                open_at DATETIME NULL,
                close_at DATETIME NULL,
                policy_version VARCHAR(20) NOT NULL DEFAULT 'v1.0',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY rp_offering_idx (offering_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS renewal_records (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                original_enrollment_id VARCHAR(36) NOT NULL,
                new_enrollment_id VARCHAR(36) NULL,
                student_id VARCHAR(36) NOT NULL,
                offering_id VARCHAR(36) NOT NULL,
                renewal_mode ENUM('new_enrollment', 'extend_existing') NOT NULL,
                previous_class_id VARCHAR(36) NOT NULL,
                new_class_id VARCHAR(36) NOT NULL,
                previous_end_date DATE NULL,
                new_end_date DATE NOT NULL,
                price_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                order_id VARCHAR(36) NULL,
                status ENUM('pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY rr_orig_idx (original_enrollment_id),
                KEY rr_new_idx (new_enrollment_id),
                KEY rr_stu_idx (student_id),
                KEY rr_off_idx (offering_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}
}
ensure_core_schema_sync($pdo);

// ----------------------------------------------------------------
// MIDTRANS — Dari environment variable
// ----------------------------------------------------------------
$midtrans_server_key = env('MIDTRANS_SERVER_KEY', '');
$midtrans_is_production = env('MIDTRANS_IS_PRODUCTION', false);

define('MIDTRANS_SERVER_KEY', $midtrans_server_key);
define('MIDTRANS_IS_PRODUCTION', $midtrans_is_production);

// ----------------------------------------------------------------
// JWT — Dari environment variable
// ----------------------------------------------------------------
$jwt_secret = env('JWT_SECRET', '');

if (empty($jwt_secret)) {
    // JWT_SECRET WAJIB ada. Tanpa ini, auth tidak bisa berjalan.
    echo json_encode([
        "success" => false,
        "error" => "Konfigurasi autentikasi tidak lengkap: JWT_SECRET belum dikonfigurasi di .env hosting.",
        "status" => "error"
    ]);
    exit;
}

define('JWT_SECRET', $jwt_secret);
define('JWT_ACCESS_TTL', (int) env('JWT_ACCESS_TTL', 86400));         // 24 jam
define('JWT_REFRESH_TTL_DAYS', (int) env('JWT_REFRESH_TTL_DAYS', 30)); // 30 hari
define('APP_ENV', env('APP_ENV', 'production'));

// ----------------------------------------------------------------
// AUDIT LOGGING HELPER
// ----------------------------------------------------------------
function log_audit(PDO $pdo, ?string $actor_id, ?string $tenant_id, string $action, ?string $target_id = null, array $metadata = [], string $level = 'INFO') {
    try {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        // Optional HMAC signature for tamper‑proofing if secret is configured
        $signature = null;
        $secret = env('AUDIT_HMAC_SECRET', null);
        if ($secret) {
            $payload = json_encode([
                'id' => $id,
                'action' => $action,
                'target_id' => $target_id,
                'metadata' => $metadata,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'level' => $level,
                'timestamp' => date('c')
            ]);
            $signature = hash_hmac('sha256', $payload, $secret);
        }
        $stmt = $pdo->prepare("INSERT INTO audit_logs (id, actor_id, tenant_id, action, target_id, metadata, ip_address, user_agent, level, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $actor_id,
            $tenant_id,
            $action,
            $target_id,
            json_encode($metadata),
            $ip,
            $ua,
            $level,
            $signature
        ]);
    } catch (Exception $e) {
        // Silently fail agar aplikasi tidak terganggu jika audit table belum ter‑migrate
    }
}

// ----------------------------------------------------------------
// SAFE STRING HELPER — Type-Safe Input Sanitizer
// Prevents TypeError crashes in PHP 8.2+ when inputs are arrays/objects/null
// ----------------------------------------------------------------
function safe_str(mixed $val, string $default = ''): string {
    if ($val === null || is_array($val) || is_object($val)) {
        return $default;
    }
    return trim((string)$val);
}
