<?php
// HARUS dijalankan via CLI saja
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden."]);
    exit;
}
require 'config.php';
try {
    $sa_id = 'uat-u-superadmin';
    $sa_ref = 'uat-r-superadmin';
    $sa_email = 'superadmin@uat.edupath.local';
    $sa_pass = 'EduPathSuperAdmin01!2026';
    $sa_hash = password_hash($sa_pass, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$sa_id, $sa_email, $sa_hash]);
    $pdo->prepare("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', ?, NULL, 'superadmin', ?)")->execute([$sa_id, $sa_ref]);
    
    $adm_id = 'uat-u-admin';
    $adm_ref = 'uat-r-admin';
    $adm_email = 'admin@uat.edupath.local';
    $adm_pass = 'EduPathAdmin01!2026';
    $adm_hash = password_hash($adm_pass, PASSWORD_BCRYPT);
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->prepare("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES (?, 'UAT EduPath Tenant', 'uat-edupath', 1)")->execute([$uat_tenant_id]);
    $pdo->prepare("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)")->execute([$adm_id, $adm_email, $adm_hash]);
    $pdo->prepare("INSERT IGNORE INTO admins (id, tenant_id, username, password, name) VALUES (?, ?, ?, ?, 'UAT Tenant Admin')")->execute([$adm_ref, $uat_tenant_id, $adm_email, $adm_hash]);
    $pdo->prepare("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', ?, ?, 'admin', ?)")->execute([$adm_id, $uat_tenant_id, $adm_ref]);
    echo json_encode(['status' => 'success']);
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
