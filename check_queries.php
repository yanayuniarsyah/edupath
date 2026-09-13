<?php
require 'api/config.php';

$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, s.id as student_id, t.is_active as tenant_active
    FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id 
    JOIN students s ON ur.reference_id = s.id
    JOIN tenants t ON ur.tenant_id = t.id
    WHERE u.identity_key = 'demo@edupath.id' AND u.is_active = 1 AND ur.role = 'student'
");
$stmt->execute();
$demo = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Demo Auth Query:\n";
print_r($demo);

$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, a.id as admin_id, t.is_active as tenant_active
    FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN admins a ON ur.reference_id = a.id
    LEFT JOIN tenants t ON ur.tenant_id = t.id
    WHERE u.identity_key = 'superadmin@uat.edupath.local' AND u.is_active = 1 AND ur.role IN ('admin', 'superadmin')
");
$stmt->execute();
$sa = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nSA Auth Query:\n";
print_r($sa);
