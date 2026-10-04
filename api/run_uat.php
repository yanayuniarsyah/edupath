<?php
require 'config.php';

try {
    $sa_id = 'uat-u-superadmin';
    $sa_ref = 'uat-r-superadmin';
    $sa_email = 'superadmin@uat.edupath.local';
    $sa_pass = 'EduPathSuperAdmin01!2026';
    $sa_hash = password_hash($sa_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('$sa_id', '$sa_email', '$sa_hash', 1)");
    $pdo->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', '$sa_id', NULL, 'superadmin', '$sa_id')");
    
    $adm_id = 'uat-u-admin';
    $adm_ref = 'uat-r-admin';
    $adm_email = 'admin@uat.edupath.local';
    $adm_pass = 'EduPathAdmin01!2026';
    $adm_hash = password_hash($adm_pass, PASSWORD_BCRYPT);
    $uat_tenant_id = 'uat-tenant-01';
    $pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES ('$uat_tenant_id', 'UAT EduPath Tenant', 'uat-edupath', 1)");
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('$adm_id', '$adm_email', '$adm_hash', 1)");
    $pdo->exec("INSERT IGNORE INTO admins (id, tenant_id, username, password, name) VALUES ('$adm_ref', '$uat_tenant_id', '$adm_email', '$adm_hash', 'UAT Tenant Admin')");
    $pdo->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', '$adm_id', '$uat_tenant_id', 'admin', '$adm_ref')");
    
    // Add admin.uat@edupath.id
    $adm2_id = 'uat2-u-admin';
    $adm2_ref = 'uat2-r-admin';
    $adm2_email = 'admin.uat@edupath.id';
    $adm2_pass = 'admin123';
    $adm2_hash = password_hash($adm2_pass, PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('$adm2_id', '$adm2_email', '$adm2_hash', 1)");
    $pdo->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat2-ur-sa', '$adm2_id', NULL, 'superadmin', '$adm2_id')");
    
    echo "<h1>SA & UAT Admin Accounts Created Successfully!</h1>";
} catch (Exception $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
?>
