<?php
require_once __DIR__ . '/api/config.php';

echo "<h1>Setup UAT Accounts - EduPath B2C (Fix Final)</h1>";

try {
    $tenant_id = "553af312-fb50-4e24-93ee-0d1abd52a62d";
    
    // Ensure tenant exists
    $pdo->prepare("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES (?, 'B2C Default Tenant', 'b2c', 1)")->execute([$tenant_id]);
    
    // Clean up old UAT data just in case
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM affiliates WHERE referral_code = 'UATAFF2025'");
    $pdo->exec("DELETE FROM user_roles WHERE reference_id IN ('STU-UAT-1', 'STU-UAT-AFF', 'ADM-UAT-1')");
    $pdo->exec("DELETE FROM admins WHERE username = 'admin.uat@edupath.id'");
    $pdo->exec("DELETE FROM students WHERE email IN ('student.uat@edupath.id', 'affiliate.uat@edupath.id')");
    $pdo->exec("DELETE FROM users WHERE identity_key IN ('student.uat@edupath.id', 'affiliate.uat@edupath.id', 'admin.uat@edupath.id')");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pass_hash = password_hash("password123", PASSWORD_BCRYPT);
    $adm_pass = password_hash("admin123", PASSWORD_BCRYPT);

    function upsert_user($pdo, $email, $password) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE id = ?")->execute([$password, $row['id']]);
            return $row['id'];
        }
        $id = "USR-" . bin2hex(random_bytes(4));
        $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$id, $email, $password]);
        return $id;
    }

    function upsert_student($pdo, $email, $name, $password, $tenant_id) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE students SET name = ?, password = ?, tenant_id = ? WHERE id = ?")->execute([$name, $password, $tenant_id, $row['id']]);
            return $row['id'];
        }
        $id = "STU-" . bin2hex(random_bytes(4));
        $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id) VALUES (?, ?, ?, ?, 'UI', ?)")->execute([$id, $name, $email, $password, $tenant_id]);
        return $id;
    }

    function upsert_admin($pdo, $username, $name, $password, $tenant_id) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE admins SET name = ?, password = ?, tenant_id = ? WHERE id = ?")->execute([$name, $password, $tenant_id, $row['id']]);
            return $row['id'];
        }
        $id = "ADM-" . bin2hex(random_bytes(4));
        $pdo->prepare("INSERT INTO admins (id, tenant_id, username, password, name) VALUES (?, ?, ?, ?, ?)")->execute([$id, $tenant_id, $username, $password, $name]);
        return $id;
    }

    function assign_role($pdo, $user_id, $tenant_id, $role, $reference_id) {
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role = ?");
        $stmt->execute([$user_id, $role]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE user_roles SET tenant_id = ?, reference_id = ? WHERE id = ?")->execute([$tenant_id, $reference_id, $row['id']]);
        } else {
            $id = "UR-" . bin2hex(random_bytes(4));
            $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, ?, ?)")->execute([$id, $user_id, $tenant_id, $role, $reference_id]);
        }
    }

    // 1. STUDENT ACCOUNT
    $stu_email = "student.uat@edupath.id";
    $u_stu_id = upsert_user($pdo, $stu_email, $pass_hash);
    $s_id = upsert_student($pdo, $stu_email, "UAT Student", $pass_hash, $tenant_id);
    assign_role($pdo, $u_stu_id, $tenant_id, 'student', $s_id);
    echo "<p>✅ Student Account: <b>$stu_email</b> (Pass: password123)</p>";

    // 2. AFFILIATE ACCOUNT
    $aff_email = "affiliate.uat@edupath.id";
    $ref_code = "UATAFF2025";
    $u_aff_id = upsert_user($pdo, $aff_email, $pass_hash);
    $s_aff_id = upsert_student($pdo, $aff_email, "UAT Affiliate", $pass_hash, $tenant_id);
    assign_role($pdo, $u_aff_id, $tenant_id, 'student', $s_aff_id);
    
    // Affiliate table
    $stmt = $pdo->prepare("SELECT id FROM affiliates WHERE user_id = ?");
    $stmt->execute([$u_aff_id]);
    $aff = $stmt->fetch();
    if ($aff) {
        $pdo->prepare("UPDATE affiliates SET referral_code = ?, tenant_id = ? WHERE id = ?")->execute([$ref_code, $tenant_id, $aff['id']]);
    } else {
        // clear if someone else has the code
        $pdo->prepare("DELETE FROM affiliates WHERE referral_code = ?")->execute([$ref_code]);
        $id = "AFF-" . bin2hex(random_bytes(4));
        $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES (?, ?, ?, ?, 20.00)")->execute([$id, $u_aff_id, $tenant_id, $ref_code]);
    }
    echo "<p>✅ Affiliate Account: <b>$aff_email</b> (Pass: password123, Code: $ref_code)</p>";

    // 3. ADMIN ACCOUNT
    $adm_email = "admin.uat@edupath.id";
    $u_adm_id = upsert_user($pdo, $adm_email, $adm_pass);
    $a_id = upsert_admin($pdo, $adm_email, "UAT Admin", $adm_pass, $tenant_id);
    assign_role($pdo, $u_adm_id, $tenant_id, 'superadmin', $a_id);
    
    echo "<p>✅ Admin Account: <b>$adm_email</b> (Pass: admin123)</p>";
    echo "<h3>PERFECT! Semua akun sudah diperbaiki. Silakan tes login.</h3>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
