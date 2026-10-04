<?php
require 'config.php';
try {
    \ = 'uat-u-superadmin';
    \ = 'uat-r-superadmin';
    \ = 'superadmin@uat.edupath.local';
    \ = 'EduPathSuperAdmin01!2026';
    \ = password_hash(\, PASSWORD_BCRYPT);
    \->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('\', '\', '\', 1)");
    \->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-sa', '\', NULL, 'superadmin', '\')");
    
    \ = 'uat-u-admin';
    \ = 'uat-r-admin';
    \ = 'admin@uat.edupath.local';
    \ = 'EduPathAdmin01!2026';
    \ = password_hash(\, PASSWORD_BCRYPT);
    \ = 'uat-tenant-01';
    \->exec("INSERT IGNORE INTO tenants (id, name, slug, is_active) VALUES ('\', 'UAT EduPath Tenant', 'uat-edupath', 1)");
    \->exec("INSERT IGNORE INTO users (id, identity_key, password, is_active) VALUES ('\', '\', '\', 1)");
    \->exec("INSERT IGNORE INTO admins (id, tenant_id, username, password, name) VALUES ('\', '\', '\', '\', 'UAT Tenant Admin')");
    \->exec("INSERT IGNORE INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('uat-ur-adm', '\', '\', 'admin', '\')");
    echo json_encode(['status' => 'success']);
} catch(Exception \) {
    echo json_encode(['error' => \->getMessage()]);
}
