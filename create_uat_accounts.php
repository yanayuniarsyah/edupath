<?php
require_once __DIR__ . '/api/config.php';

echo "<h1>Setup UAT Accounts - EduPath B2C</h1>";

try {
    // 1. Create a Student Account
    $student_id = "STU-UAT-" . bin2hex(random_bytes(4));
    $student_email = "student.uat@edupath.id";
    $student_pass = password_hash("password123", PASSWORD_BCRYPT);
    $tenant_id = "default_tenant"; // Default for B2C

    // Check if student exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$student_email]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)")->execute([$student_id, $student_email, $student_pass]);
        $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$student_id, "UAT Student", $student_email, $student_pass, "Universitas Indonesia - Ilmu Komputer", $tenant_id]);
        echo "<p>✅ Created Student Account: <b>$student_email</b> (Pass: password123)</p>";
    } else {
        echo "<p>✅ Student Account already exists: <b>$student_email</b> (Pass: password123)</p>";
    }

    // 2. Create an Affiliate Account
    $user_id = "USR-UAT-" . bin2hex(random_bytes(4));
    $affiliate_id = "AFF-UAT-" . bin2hex(random_bytes(4));
    $affiliate_email = "affiliate.uat@edupath.id";
    $referral_code = "UATAFF2025";
    $affiliate_pass = password_hash("password123", PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$affiliate_email]);
    $existing_user = $stmt->fetch();
    
    if (!$existing_user) {
        // Create user
        $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)")
            ->execute([$user_id, $affiliate_email, $affiliate_pass]);
        
        // Make user an affiliate
        $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES (?, ?, ?, ?, ?)")
            ->execute([$affiliate_id, $user_id, $tenant_id, $referral_code, 20.00]);
            
        echo "<p>✅ Created Affiliate Account: <b>$affiliate_email</b> (Pass: password123, Code: $referral_code)</p>";
    } else {
        echo "<p>✅ Affiliate Account already exists: <b>$affiliate_email</b> (Pass: password123, Code: $referral_code)</p>";
    }

    // 3. Create a Superadmin Account
    $admin_id = "ADM-UAT-" . bin2hex(random_bytes(4));
    $admin_email = "admin.uat@edupath.id";
    $admin_pass = password_hash("admin123", PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$admin_email]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO admins (id, username, password_hash, role) VALUES (?, ?, ?, ?)")
            ->execute([$admin_id, $admin_email, $admin_pass, 'superadmin']);
        echo "<p>✅ Created Superadmin Account: <b>$admin_email</b> (Pass: admin123)</p>";
    } else {
        echo "<p>✅ Superadmin Account already exists: <b>$admin_email</b> (Pass: admin123)</p>";
    }

    echo "<h3>Semua akun UAT berhasil disiapkan. Harap hapus file ini di production!</h3>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
