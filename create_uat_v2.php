<?php
require 'api/config.php';

echo "EDUPATH UAT ACCOUNT PROVISIONING\n";
echo "=================================\n";

$tests = [];

// Helper to assert
function assertTest($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
}

// Helper to make CURL requests
function api_request($endpoint, $method = 'GET', $token = null, $data = null) {
    $ch = curl_init();
    $url = "http://localhost:8000/" . ltrim($endpoint, '/');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = "Content-Type: application/json";
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpcode, 'body' => json_decode($result, true) ?? $result];
}

try {
    $pdo->beginTransaction();

    // Create UAT Tenant
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->exec("INSERT INTO tenants (id, name, slug, is_active) VALUES ('$uat_tenant_id', 'UAT EduPath Tenant', 'uat-edupath', 1) ON DUPLICATE KEY UPDATE name=name");

    $accounts = [];

    // 1. Super Admin
    $sa_id = 'uat-u-superadmin';
    $sa_ref = 'uat-r-superadmin';
    $sa_email = 'superadmin@uat.edupath.local';
    $sa_pass = 'EduPathSuperAdmin01!2026';
    $sa_hash = password_hash($sa_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$sa_id', '$sa_email', '$sa_hash', 1) ON DUPLICATE KEY UPDATE password='$sa_hash'");
    // Superadmin does not need an admins table entry, reference_id is just user_id
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', '$sa_id', NULL, 'superadmin', '$sa_id') ON DUPLICATE KEY UPDATE role=role");

    // 2. Tenant Admin
    $adm_id = 'uat-u-admin';
    $adm_ref = 'uat-r-admin';
    $adm_email = 'admin@uat.edupath.local';
    $adm_pass = 'EduPathAdmin01!2026';
    $adm_hash = password_hash($adm_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$adm_id', '$adm_email', '$adm_hash', 1) ON DUPLICATE KEY UPDATE password='$adm_hash'");
    $pdo->exec("INSERT INTO admins (id, tenant_id, username, password, name) VALUES ('$adm_ref', '$uat_tenant_id', '$adm_email', '$adm_hash', 'UAT Tenant Admin') ON DUPLICATE KEY UPDATE name=name");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', '$adm_id', '$uat_tenant_id', 'admin', '$adm_ref') ON DUPLICATE KEY UPDATE role=role");

    // 3. Student
    $stu_id = 'uat-u-student';
    $stu_ref = 'uat-r-student';
    $stu_email = 'student@uat.edupath.local';
    $stu_pass = 'EduPathStudent01!2026';
    $stu_hash = password_hash($stu_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$stu_id', '$stu_email', '$stu_hash', 1) ON DUPLICATE KEY UPDATE password='$stu_hash'");
    $pdo->exec("INSERT INTO students (id, name, email, password, tenant_id) VALUES ('$stu_ref', 'UAT Student', '$stu_email', '$stu_hash', '$uat_tenant_id') ON DUPLICATE KEY UPDATE password='$stu_hash'");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-stu', '$stu_id', '$uat_tenant_id', 'student', '$stu_ref') ON DUPLICATE KEY UPDATE role=role");

    // 4. Affiliate
    $aff_id = 'uat-u-affiliate';
    $aff_ref = 'uat-r-affiliate';
    $aff_email = 'affiliate@uat.edupath.local';
    $aff_pass = 'EduPathAffiliate01!2026';
    $aff_hash = password_hash($aff_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$aff_id', '$aff_email', '$aff_hash', 1) ON DUPLICATE KEY UPDATE password='$aff_hash'");
    $pdo->exec("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES ('$aff_ref', '$aff_id', '$uat_tenant_id', 'UAT-AFF', 10.00) ON DUPLICATE KEY UPDATE referral_code=referral_code");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-aff', '$aff_id', '$uat_tenant_id', 'affiliate', '$aff_ref') ON DUPLICATE KEY UPDATE role=role");

    // 5. Teacher (Equivalent to Tutor)
    $tch_id = 'uat-u-teacher';
    $tch_ref = 'uat-r-teacher';
    $tch_email = 'tutor@uat.edupath.local';
    $tch_pass = 'EduPathTutor01!2026';
    $tch_hash = password_hash($tch_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$tch_id', '$tch_email', '$tch_hash', 1) ON DUPLICATE KEY UPDATE password='$tch_hash'");
    $pdo->exec("INSERT INTO admins (id, tenant_id, username, password, name) VALUES ('$tch_ref', '$uat_tenant_id', '$tch_email', '$tch_hash', 'UAT Tutor/Teacher') ON DUPLICATE KEY UPDATE name=name");
    $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-tch', '$tch_id', '$uat_tenant_id', 'teacher', '$tch_ref') ON DUPLICATE KEY UPDATE role=role");

    $pdo->commit();
    echo "DB Insertions committed.\n";

    // Now perform HTTP API login
    
    // Test Superadmin
    $res = api_request('admin.php?action=login', 'POST', null, ['username' => $sa_email, 'password' => $sa_pass]);
    assertTest($res['code'] === 200 && isset($res['body']['token']), "Superadmin login failed!");
    assertTest($res['body']['user']['role'] === 'superadmin', "Superadmin role mismatched!");
    $sa_token = $res['body']['token'];
    echo "Superadmin login verified.\n";

    // Test Tenant Admin
    $res = api_request('admin.php?action=login', 'POST', null, ['username' => $adm_email, 'password' => $adm_pass]);
    assertTest($res['code'] === 200 && isset($res['body']['token']), "Tenant Admin login failed!");
    assertTest($res['body']['user']['role'] === 'admin', "Tenant Admin role mismatched!");
    $adm_token = $res['body']['token'];
    echo "Tenant Admin login verified.\n";

    // Test Student
    $res = api_request('auth.php?action=login', 'POST', null, ['email' => $stu_email, 'password' => $stu_pass]);
    assertTest($res['code'] === 200 && isset($res['body']['token']), "Student login failed!");
    assertTest($res['body']['user']['role'] === 'student', "Student role mismatched!");
    $stu_token = $res['body']['token'];
    echo "Student login verified.\n";

    // Note: Affiliate and Teacher login endpoints do not currently exist in the API layer, so we cannot test them.

    // Authorization Boundary Tests
    // 1. Superadmin can list tenants
    $res = api_request('admin.php?action=tenants', 'GET', $sa_token);
    assertTest($res['code'] === 200, "Superadmin cannot access global tenants");

    // 2. Admin CANNOT list tenants
    $res = api_request('admin.php?action=tenants', 'GET', $adm_token);
    assertTest($res['code'] === 403, "Tenant Admin breached global boundaries");

    // 3. Admin can list students in tenant
    $res = api_request('admin.php?action=students', 'GET', $adm_token);
    assertTest($res['code'] === 200, "Tenant Admin cannot access tenant students");

    // 4. Student CANNOT access admin endpoints
    $res = api_request('admin.php?action=students', 'GET', $stu_token);
    assertTest($res['code'] === 403, "Student breached admin boundaries");

    echo "Authorization boundaries verified.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
