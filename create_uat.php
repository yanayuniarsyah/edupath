<?php
require 'api/config.php';
require 'api/jwt.php';

header('Content-Type: application/json');

try {
    // Audit existing roles from the DB or known logic
    $roles_stmt = $pdo->query("SELECT DISTINCT role FROM user_roles");
    $existing_roles = $roles_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Some roles might not have data yet, so we also check common code usage.
    // In our system, 'admin', 'superadmin', 'student' are definitely used.
    $supported_roles = array_unique(array_merge($existing_roles, ['admin', 'superadmin', 'student', 'affiliate', 'tutor']));
    
    // Check if affiliates table exists
    $has_affiliate = $pdo->query("SHOW TABLES LIKE 'affiliates'")->rowCount() > 0;
    // Check if tutors table exists
    $has_tutor = $pdo->query("SHOW TABLES LIKE 'tutors'")->rowCount() > 0;

    $pdo->beginTransaction();

    // 1. Create UAT Tenant
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->exec("INSERT INTO tenants (id, name, is_active) VALUES ('$uat_tenant_id', 'UAT EduPath Tenant', 1) ON DUPLICATE KEY UPDATE name=name");

    $accounts = [];

    // Helper to create user
    $create_user = function($role, $email, $password, $name, $tenant_id) use ($pdo, &$accounts) {
        $user_id = 'uat-u-' . $role;
        $ref_id = 'uat-r-' . $role;
        $ur_id = 'uat-ur-' . $role;
        
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$user_id', '$email', '$hashed', 1) ON DUPLICATE KEY UPDATE password='$hashed'");
        
        if ($role === 'superadmin') {
            $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('$ur_id', '$user_id', NULL, '$role', '$ref_id') ON DUPLICATE KEY UPDATE role=role");
        } else {
            $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('$ur_id', '$user_id', '$tenant_id', '$role', '$ref_id') ON DUPLICATE KEY UPDATE role=role");
        }

        if ($role === 'student') {
            $pdo->exec("INSERT INTO students (id, name, email, password, tenant_id) VALUES ('$ref_id', '$name', '$email', '$hashed', '$tenant_id') ON DUPLICATE KEY UPDATE password='$hashed'");
        } elseif ($role === 'affiliate') {
            $pdo->exec("INSERT INTO affiliates (id, tenant_id, name, email, phone, bank_name, bank_account, referral_code) VALUES ('$ref_id', '$tenant_id', '$name', '$email', '000', 'Bank', '000', 'UAT-AFF-01') ON DUPLICATE KEY UPDATE name=name");
        }
        
        $accounts[] = [
            'Name' => $name,
            'Email' => $email,
            'Role' => $role,
            'Tenant' => $tenant_id,
            'Purpose' => 'UAT Testing',
            'user_id' => $user_id
        ];
    };

    // Create Super Admin
    $create_user('superadmin', 'superadmin@uat.edupath.local', 'EduPathSuperAdmin01!2026', 'UAT Super Admin', null);
    // Create Tenant Admin
    $create_user('admin', 'admin@uat.edupath.local', 'EduPathAdmin01!2026', 'UAT Tenant Admin', $uat_tenant_id);
    // Create Student
    $create_user('student', 'student@uat.edupath.local', 'EduPathStudent01!2026', 'UAT Student', $uat_tenant_id);
    // Create Affiliate if exists
    if ($has_affiliate) {
        $create_user('affiliate', 'affiliate@uat.edupath.local', 'EduPathAffiliate01!2026', 'UAT Affiliate', $uat_tenant_id);
    }
    // Create Tutor if exists
    if ($has_tutor) {
        $create_user('tutor', 'tutor@uat.edupath.local', 'EduPathTutor01!2026', 'UAT Tutor', $uat_tenant_id);
    }

    $pdo->commit();

    echo json_encode(["success" => true, "accounts" => $accounts, "has_affiliate" => $has_affiliate, "has_tutor" => $has_tutor]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["error" => $e->getMessage()]);
}
