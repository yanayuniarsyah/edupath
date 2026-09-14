<?php
require_once __DIR__ . '/api/config.php';

echo "<h1>Setup UAT Accounts - EduPath B2C (Fix Final)</h1>";

try {
    $tenant_id = "553af312-fb50-4e24-93ee-0d1abd52a62d";
    
    // Ensure tenant exists
    $pdo->prepare("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES (?, 'B2C Default Tenant', 'b2c', 1)")->execute([$tenant_id]);
    
    // Clean up old UAT data just in case
    $pdo->exec("DELETE FROM affiliates WHERE referral_code = 'UATAFF2025'");
    $pdo->exec("DELETE FROM admins WHERE username = 'admin.uat@edupath.id'");
    $pdo->exec("DELETE FROM students WHERE email IN ('student.uat@edupath.id', 'affiliate.uat@edupath.id')");
    $pdo->exec("DELETE FROM users WHERE identity_key IN ('student.uat@edupath.id', 'affiliate.uat@edupath.id', 'admin.uat@edupath.id')");

    // 1. STUDENT ACCOUNT
    $u_stu_id = "USR-UAT-STU";
    $s_id = "STU-UAT-1";
    $stu_email = "student.uat@edupath.id";
    $pass_hash = password_hash("password123", PASSWORD_BCRYPT);
    
    $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$u_stu_id, $stu_email, $pass_hash]);
    $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id) VALUES (?, ?, ?, ?, ?, ?)")->execute([$s_id, "UAT Student", $stu_email, $pass_hash, "UI", $tenant_id]);
    $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)")->execute(["UR-STU", $u_stu_id, $tenant_id, $s_id]);
    
    echo "<p>✅ Student Account: <b>$stu_email</b> (Pass: password123)</p>";

    // 2. AFFILIATE ACCOUNT (must also be a student to login to B2C panel)
    $u_aff_id = "USR-UAT-AFF";
    $s_aff_id = "STU-UAT-AFF";
    $aff_id = "AFF-UAT-1";
    $aff_email = "affiliate.uat@edupath.id";
    $ref_code = "UATAFF2025";
    
    $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$u_aff_id, $aff_email, $pass_hash]);
    $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id) VALUES (?, ?, ?, ?, ?, ?)")->execute([$s_aff_id, "UAT Affiliate", $aff_email, $pass_hash, "UI", $tenant_id]);
    $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)")->execute(["UR-AFF", $u_aff_id, $tenant_id, $s_aff_id]);
    $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES (?, ?, ?, ?, 20.00)")->execute([$aff_id, $u_aff_id, $tenant_id, $ref_code]);
    
    echo "<p>✅ Affiliate Account: <b>$aff_email</b> (Pass: password123, Code: $ref_code)</p>";

    // 3. ADMIN ACCOUNT
    $u_adm_id = "USR-UAT-ADM";
    $a_id = "ADM-UAT-1";
    $adm_email = "admin.uat@edupath.id";
    $adm_pass = password_hash("admin123", PASSWORD_BCRYPT);
    
    $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$u_adm_id, $adm_email, $adm_pass]);
    $pdo->prepare("INSERT INTO admins (id, tenant_id, username, password, name) VALUES (?, ?, ?, ?, ?)")->execute([$a_id, $tenant_id, $adm_email, $adm_pass, "UAT Admin"]);
    $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'superadmin', ?)")->execute(["UR-ADM", $u_adm_id, $tenant_id, $a_id]);
    
    echo "<p>✅ Admin Account: <b>$adm_email</b> (Pass: admin123)</p>";
    echo "<h3>PERFECT! Semua akun sudah diperbaiki. Silakan tes login.</h3>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
