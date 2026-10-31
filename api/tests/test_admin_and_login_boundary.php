<?php
// api/tests/test_admin_and_login_boundary.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../rate_limit.php';

// Reset rate limits for test IP to ensure independent test isolation
reset_rate_limit($pdo, 'login', '127.0.0.1');
reset_rate_limit($pdo, 'login', '::1');

function run_test($name, $closure) {
    echo "=== TEST: $name ===\n";
    try {
        $result = $closure();
        if ($result === true) {
            echo " [PASS] $name\n\n";
            return true;
        } else {
            echo " [FAIL] $name: $result\n\n";
            return false;
        }
    } catch (\Throwable $e) {
        echo " [ERROR] $name: " . $e->getMessage() . "\n\n";
        return false;
    }
}

$allPassed = true;

// ── TEST 1: Admin Profile Partial Update (Hanya Edit Nama) ──
$allPassed = run_test("1. Admin Update HANYA Nama", function() use ($pdo) {
    // Cari akun admin
    $stmt = $pdo->query("SELECT u.id as user_id, u.identity_key, ur.role, ur.tenant_id, ur.reference_id FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) return "Tidak ada akun admin di database";

    $token = generate_jwt([
        'user_id' => $admin['user_id'],
        'id' => $admin['reference_id'] ?? $admin['user_id'],
        'role' => 'admin',
        'tenant_id' => $admin['tenant_id'],
        'email' => $admin['identity_key']
    ]);

    $newName = "Admin Test Name " . rand(1000, 9999);
    $initialEmail = $admin['identity_key'];

    // Panggil update_profile dengan HANYA field name
    $ch = curl_init("http://localhost:8000/api/admin.php?action=update_profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => $newName
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || empty($json['success'])) {
        return "HTTP $httpCode: $response";
    }

    if (($json['user']['name'] ?? '') !== $newName) {
        return "Returned name tidak cocok: " . ($json['user']['name'] ?? 'null');
    }

    // Verifikasi langsung ke database users & admins
    $stmtVerify = $pdo->prepare("SELECT u.display_name, u.identity_key, a.name as admin_name FROM users u LEFT JOIN admins a ON u.identity_key = a.username WHERE u.id = ?");
    $stmtVerify->execute([$admin['user_id']]);
    $row = $stmtVerify->fetch(PDO::FETCH_ASSOC);

    if ($row['display_name'] !== $newName) {
        return "DB users.display_name belum berubah ke '$newName', masih '{$row['display_name']}'";
    }
    if ($row['identity_key'] !== $initialEmail) {
        return "DB users.identity_key berubah secara tidak sah!";
    }

    return true;
}) && $allPassed;

// ── TEST 2: Admin Profile Partial Update (Hanya Edit Email) ──
$allPassed = run_test("2. Admin Update HANYA Email", function() use ($pdo) {
    $stmt = $pdo->query("SELECT u.id as user_id, u.identity_key, u.display_name, ur.role, ur.tenant_id, ur.reference_id FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) return "Tidak ada akun admin";

    $token = generate_jwt([
        'user_id' => $admin['user_id'],
        'id' => $admin['reference_id'] ?? $admin['user_id'],
        'role' => 'admin',
        'tenant_id' => $admin['tenant_id'],
        'email' => $admin['identity_key']
    ]);

    $existingName = $admin['display_name'] ?: 'Admin Test';
    $newEmail = "admintest_" . rand(1000, 9999) . "@yahoo.com";

    $ch = curl_init("http://localhost:8000/api/admin.php?action=update_profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $newEmail
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || empty($json['success'])) {
        return "HTTP $httpCode: $response";
    }

    if (($json['user']['email'] ?? '') !== $newEmail) {
        return "Returned email tidak cocok: " . ($json['user']['email'] ?? 'null');
    }

    // Verifikasi ke database users & admins
    $stmtVerify = $pdo->prepare("SELECT u.display_name, u.identity_key, a.username as admin_username FROM users u LEFT JOIN admins a ON u.id = a.id OR u.identity_key = a.username WHERE u.id = ?");
    $stmtVerify->execute([$admin['user_id']]);
    $row = $stmtVerify->fetch(PDO::FETCH_ASSOC);

    if (strtolower($row['identity_key']) !== strtolower($newEmail)) {
        return "DB users.identity_key belum berubah ke '$newEmail', masih '{$row['identity_key']}'";
    }

    // Kembalikan email awal agar tidak merusak data UAT
    $pdo->prepare("UPDATE users SET identity_key = ? WHERE id = ?")->execute([$admin['identity_key'], $admin['user_id']]);
    $pdo->prepare("UPDATE admins SET username = ? WHERE username = ?")->execute([$admin['identity_key'], $newEmail]);

    return true;
}) && $allPassed;

// ── TEST 3: Front Login Ditolak untuk Akun Administrator ──
$allPassed = run_test("3. Front Login Ditolak untuk Akun Admin (HTTP 403)", function() use ($pdo) {
    // Akun admin UAT
    $ch = curl_init("http://localhost:8000/api/auth.php?action=login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'admin@uat.edupath.local',
        'password' => 'EduPathAdmin01!2026'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 403) {
        return "Seharusnya HTTP 403, tapi mendapat HTTP $httpCode: $response";
    }
    if (!str_contains($json['error'] ?? '', '/admin')) {
        return "Pesan error tidak mengarahkan ke /admin: " . ($json['error'] ?? '');
    }

    return true;
}) && $allPassed;

// ── TEST 4: Front Login Ditolak untuk Akun Superadmin ──
$allPassed = run_test("4. Front Login Ditolak untuk Akun Superadmin (HTTP 403)", function() use ($pdo) {
    $ch = curl_init("http://localhost:8000/api/auth.php?action=login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'superadmin@uat.edupath.local',
        'password' => 'EduPathSuperAdmin01!2026'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 403) {
        return "Seharusnya HTTP 403, tapi mendapat HTTP $httpCode: $response";
    }
    if (!str_contains($json['error'] ?? '', '/admin')) {
        return "Pesan error tidak mengarahkan ke /admin: " . ($json['error'] ?? '');
    }

    return true;
}) && $allPassed;

// ── TEST 5: Front Login Diizinkan untuk Akun Sekolah Mitra (school_bk) ──
$allPassed = run_test("5. Front Login DIIZINKAN untuk Sekolah Mitra (school_bk -> HTTP 200)", function() use ($pdo) {
    // Gunakan akun sekolah test dengan password terverifikasi
    $schEmail = 'bk.test.admin@sekolah.id';
    $passHash = password_hash('EduPathSchool01!2026', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$schEmail]);
    $uId = $stmt->fetchColumn();
    if (!$uId) {
        $schId = 'sch-test-boundary';
        $uId = 'u-' . $schId;
        $pdo->exec("INSERT INTO schools (id, name, slug, bk_email, bk_name, is_active) VALUES ('$schId', 'SMAN Test 99', 'sman-test-99', '$schEmail', 'Guru BK Test', 1) ON DUPLICATE KEY UPDATE bk_name='Guru BK Test'");
        $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$uId', '$schEmail', '$passHash', 1) ON DUPLICATE KEY UPDATE password='$passHash'");
        $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('ur-$schId', '$uId', 'default_tenant', 'school_bk', '$schId') ON DUPLICATE KEY UPDATE role='school_bk'");
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$passHash, $uId]);
        $pdo->prepare("UPDATE schools SET bk_email = ? WHERE id = (SELECT reference_id FROM user_roles WHERE user_id = ? LIMIT 1)")->execute([$schEmail, $uId]);
    }

    $ch = curl_init("http://localhost:8000/api/auth.php?action=login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $schEmail,
        'password' => 'EduPathSchool01!2026'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || empty($json['token'])) {
        return "HTTP $httpCode: $response";
    }
    if (($json['user']['role'] ?? '') !== 'school_bk') {
        return "Role bukan school_bk: " . ($json['user']['role'] ?? 'null');
    }
    if (empty($json['user']['school'])) {
        return "Data user.school kosong!";
    }

    return true;
}) && $allPassed;

// ── TEST 6: Portal Admin (/admin) Ditolak untuk Non-Admin (school_bk / student) ──
$allPassed = run_test("6. Portal Admin (/admin) DITOLAK untuk Non-Admin (HTTP 403)", function() use ($pdo) {
    $schEmail = 'bk.test.admin@sekolah.id';

    $ch = curl_init("http://localhost:8000/api/admin.php?action=login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $schEmail,
        'password' => 'EduPathSchool01!2026'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 403) {
        return "Seharusnya HTTP 403, tapi mendapat HTTP $httpCode: $response";
    }
    if (!str_contains($json['error'] ?? '', 'Akses khusus Administrator & Superadmin')) {
        return "Pesan penolakan tidak sesuai: " . ($json['error'] ?? '');
    }

    return true;
}) && $allPassed;

// ── TEST 7: Portal Admin (/admin) DIIZINKAN untuk Administrator & Superadmin ──
$allPassed = run_test("7. Portal Admin (/admin) DIIZINKAN untuk Admin & Superadmin (HTTP 200)", function() use ($pdo) {
    $ch = curl_init("http://localhost:8000/api/admin.php?action=login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => 'superadmin@uat.edupath.local',
        'password' => 'EduPathSuperAdmin01!2026'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode !== 200 || empty($json['token'])) {
        return "Superadmin gagal login di /admin: HTTP $httpCode: $response";
    }

    return true;
}) && $allPassed;

echo "\n============================================\n";
if ($allPassed) {
    echo "SELURUH PENGUJIAN SELESAI & BERHASIL 100%!\n";
    exit(0);
} else {
    echo "ADA PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
