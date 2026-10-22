<?php
require_once __DIR__ . '/config.php';

// 1. Ensure admin and superadmin are Superadmin
$pdo->exec("UPDATE user_roles ur JOIN users u ON ur.user_id = u.id SET ur.role = 'superadmin', ur.tenant_id = NULL WHERE u.identity_key IN ('admin', 'superadmin', 'admin.uat@edupath.id', 'superadmin@uat.edupath.local')");

// 2. Ensure admin@uat.edupath.local is Tenant Admin
$pdo->exec("INSERT INTO tenants (id, name, slug, is_active) VALUES ('uat-tenant-01', 'UAT EduPath Tenant', 'uat-edupath', 1) ON DUPLICATE KEY UPDATE is_active=1");
$pdo->exec("UPDATE user_roles ur JOIN users u ON ur.user_id = u.id SET ur.role = 'admin', ur.tenant_id = 'uat-tenant-01' WHERE u.identity_key = 'admin@uat.edupath.local'");

echo "Roles synced successfully!\n";
