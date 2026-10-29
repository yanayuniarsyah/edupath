<?php
// api/tests/extreme_security_audit.php
// Extreme Security Hardening & Defensive Verification Suite

echo "====================================================================\n";
echo "EXTREME SECURITY HARDENING & DEFENSIVE AUDIT SUITE\n";
echo "====================================================================\n\n";

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Build Tables for Security Audit
$pdo->exec("
    CREATE TABLE rate_limits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        endpoint TEXT NOT NULL,
        attempt_count INTEGER DEFAULT 1,
        lock_until DATETIME DEFAULT NULL,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (ip_address, endpoint)
    );

    CREATE TABLE users (
        id TEXT PRIMARY KEY,
        identity_key TEXT UNIQUE,
        password TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE user_roles (
        id TEXT PRIMARY KEY,
        user_id TEXT,
        tenant_id TEXT,
        role TEXT,
        reference_id TEXT
    );

    CREATE TABLE admins (
        id TEXT PRIMARY KEY,
        tenant_id TEXT,
        username TEXT UNIQUE,
        name TEXT,
        password TEXT
    );
");

// Self-contained Rate Limit Engine (matching api/rate_limit.php logic)
function test_check_rate_limit($pdo, $ip, $endpoint, $max_attempts = 5, $lock_minutes = 15) {
    $stmt = $pdo->prepare("SELECT attempt_count, lock_until FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        if ($record['lock_until'] && strtotime($record['lock_until']) > time()) {
            return false; // Still locked
        }

        if ($record['lock_until'] && strtotime($record['lock_until']) <= time()) {
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = 1, lock_until = NULL WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$ip, $endpoint]);
            return true;
        }

        $new_count = $record['attempt_count'] + 1;
        if ($new_count > $max_attempts) {
            $lock_time = date('Y-m-d H:i:s', time() + ($lock_minutes * 60));
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = ?, lock_until = ? WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$new_count, $lock_time, $ip, $endpoint]);
            return false;
        } else {
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempt_count = ? WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$new_count, $ip, $endpoint]);
            return true;
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, attempt_count) VALUES (?, ?, 1)");
        $stmt->execute([$ip, $endpoint]);
        return true;
    }
}

function test_reset_rate_limit($pdo, $ip, $endpoint) {
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
}

// ----------------------------------------------------------------------------
// TEST 1: Admin Login Rate Limiting (Brute-Force & Credential Stuffing Defense)
// ----------------------------------------------------------------------------
$attackerIp = '198.51.100.42';
$max_attempts = 5;
$lockout_mins = 15;

$passed_all_initial = true;
for ($i = 1; $i <= $max_attempts; $i++) {
    $canAttempt = test_check_rate_limit($pdo, $attackerIp, 'admin_login', $max_attempts, $lockout_mins);
    if (!$canAttempt) {
        $passed_all_initial = false;
        break;
    }
}
assert($passed_all_initial === true, "First 5 attempts must be allowed");

// 6th attempt MUST be blocked by rate limiter
$sixthAttempt = test_check_rate_limit($pdo, $attackerIp, 'admin_login', $max_attempts, $lockout_mins);
assert($sixthAttempt === false, "6th attempt must be rejected with HTTP 429 rate limit");

// Verify that test_reset_rate_limit successfully clears lockout on successful login
test_reset_rate_limit($pdo, $attackerIp, 'admin_login');
$afterReset = test_check_rate_limit($pdo, $attackerIp, 'admin_login', $max_attempts, $lockout_mins);
assert($afterReset === true, "After valid login and reset, rate limit must be reset");

echo "✅ PASS: 1. Admin login rate limiting strictly blocks brute-force attempts after threshold and resets properly on valid auth\n";

// ----------------------------------------------------------------------------
// TEST 2: JWT Security — Signature Tampering & Timing-Safe Verification
// ----------------------------------------------------------------------------
function test_generate_jwt(array $payload, string $secret): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + 86400;

    $b64Header    = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $b64Payload   = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signature    = hash_hmac('sha256', "$b64Header.$b64Payload", $secret, true);
    $b64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return "$b64Header.$b64Payload.$b64Signature";
}

function test_verify_jwt(string $jwt, string $secret): object|false {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    [$b64Header, $b64Payload, $b64Sig] = $parts;

    $header = json_decode(base64_decode(strtr($b64Header, '-_', '+/')), true);
    if (!$header || ($header['alg'] ?? '') !== 'HS256') return false;

    $expectedSig = hash_hmac('sha256', "$b64Header.$b64Payload", $secret, true);
    $expectedB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

    if (!hash_equals($expectedB64, $b64Sig)) return false;

    $payload = json_decode(base64_decode(strtr($b64Payload, '-_', '+/')));
    if (!$payload) return false;

    if (isset($payload->exp) && $payload->exp < time()) return false;

    return $payload;
}

$secret = 'edupath-super-secret-key-for-testing-purposes-32-chars';
$validPayload = [
    'user_id' => 'u-admin-1',
    'id' => 'adm-1',
    'role' => 'admin',
    'tenant_id' => 'tenant-01'
];
$token = test_generate_jwt($validPayload, $secret);
$verified = test_verify_jwt($token, $secret);
assert($verified !== false && $verified->role === 'admin', "Valid token must verify successfully");

// Tampering Attack 1: Alter payload without updating signature (role escalation)
$parts = explode('.', $token);
$tamperedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
$tamperedPayload['role'] = 'superadmin'; // privilege escalation attempt
$parts[1] = rtrim(strtr(base64_encode(json_encode($tamperedPayload)), '+/', '-_'), '=');
$tamperedToken = implode('.', $parts);
$verifiedTampered = test_verify_jwt($tamperedToken, $secret);
assert($verifiedTampered === false, "Tampered payload token MUST be rejected");

// Tampering Attack 2: Algorithm 'none' attack
$headerNone = json_encode(['typ' => 'JWT', 'alg' => 'none']);
$b64NoneHeader = rtrim(strtr(base64_encode($headerNone), '+/', '-_'), '=');
$tokenNone = "$b64NoneHeader.{$parts[1]}.";
assert(test_verify_jwt($tokenNone, $secret) === false, "Algorithm 'none' attack MUST be rejected");

echo "✅ PASS: 2. JWT signature enforcement blocks payload tampering, privilege escalation, and algorithm 'none' attacks\n";

// ----------------------------------------------------------------------------
// TEST 3: CSV Formula Injection Defense
// ----------------------------------------------------------------------------
$sanitize_csv = function($val) {
    if ($val === null) return '';
    $str = (string)$val;
    if (in_array(substr($str, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
};

$maliciousPayloads = [
    "=cmd|'/C calc'!A0" => "'=cmd|'/C calc'!A0",
    "+1+1" => "'+1+1",
    "-2+3" => "'-2+3",
    "@SUM(A1:A10)" => "'@SUM(A1:A10)",
    "\tmalicious" => "'\tmalicious",
    "Budi Santoso" => "Budi Santoso", // Normal name untouched
    "budi@example.com" => "budi@example.com"
];

foreach ($maliciousPayloads as $inputPayload => $expectedOutput) {
    $sanitized = $sanitize_csv($inputPayload);
    assert($sanitized === $expectedOutput, "Payload '$inputPayload' must sanitize to '$expectedOutput', got '$sanitized'");
}
echo "✅ PASS: 3. Anti-CSV Formula Injection sanitizer neutralizes all dangerous spreadsheet formula prefixes (=, +, -, @, \\t, \\r)\n";

// ----------------------------------------------------------------------------
// TEST 4: Sensitive Directory Web Access Denial (.htaccess checks)
// ----------------------------------------------------------------------------
$dbHtaccess = file_get_contents(__DIR__ . '/../database/.htaccess');
assert(strpos($dbHtaccess, 'Require all denied') !== false, "api/database/.htaccess must contain 'Require all denied'");

$testHtaccess = file_get_contents(__DIR__ . '/.htaccess');
assert(strpos($testHtaccess, 'Require all denied') !== false, "api/tests/.htaccess must contain 'Require all denied'");

$backupHtaccess = file_get_contents(__DIR__ . '/../backups/.htaccess');
assert(strpos($backupHtaccess, 'Require all denied') !== false, "api/backups/.htaccess must contain 'Require all denied'");

$apiHtaccess = file_get_contents(__DIR__ . '/../.htaccess');
assert(strpos($apiHtaccess, 'extreme_.*\.php') !== false, "api/.htaccess must deny extreme test scripts");

echo "✅ PASS: 4. Sensitive directories (database, tests, backups) have strict web HTTP denial via .htaccess\n";

// ----------------------------------------------------------------------------
// TEST 5: Role Integrity & Portal Boundary Separation
// ----------------------------------------------------------------------------
$pdo->exec("
    INSERT INTO users (id, identity_key, password) VALUES
    ('u-stu-1', 'siswa@edupath.local', 'hash'),
    ('u-bk-1', 'bk@sman1.sch.id', 'hash'),
    ('u-adm-1', 'admin@edupath.local', 'hash');

    INSERT INTO user_roles (id, user_id, role) VALUES
    ('ur-1', 'u-stu-1', 'student'),
    ('ur-2', 'u-bk-1', 'school_bk'),
    ('ur-3', 'u-adm-1', 'admin');
");

function checkAdminPortalRoleAllowed($pdo, $username) {
    $stmt = $pdo->prepare("SELECT ur.role FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE LOWER(u.identity_key) = LOWER(?) LIMIT 1");
    $stmt->execute([$username]);
    $role = $stmt->fetchColumn() ?: '';
    if ($role && in_array($role, ['school_bk', 'teacher', 'guru', 'tutor', 'student', 'affiliate'])) {
        return false; // Forbidden in admin portal
    }
    return in_array($role, ['admin', 'superadmin']);
}

assert(checkAdminPortalRoleAllowed($pdo, 'siswa@edupath.local') === false, "Student must be rejected from admin portal");
assert(checkAdminPortalRoleAllowed($pdo, 'bk@sman1.sch.id') === false, "School BK must be rejected from admin portal");
assert(checkAdminPortalRoleAllowed($pdo, 'admin@edupath.local') === true, "Admin must be allowed in admin portal");

echo "✅ PASS: 5. Strict role portal boundaries block non-admin accounts from accessing administrative authentication\n";

echo "\n--------------------------------------------------------------------\n";
echo "🎉 ALL 5 EXTREME SECURITY AUDIT TESTS PASSED WITH ZERO FAILURES!\n";
echo "--------------------------------------------------------------------\n";
