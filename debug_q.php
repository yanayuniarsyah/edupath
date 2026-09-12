<?php
require 'api/config.php';

// Hit API questions as superadmin
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/admin.php?action=login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$login = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $login['token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/admin.php?action=questions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$result = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($result, true);
echo "HTTP $code | Count: " . count($data) . "\n";
echo "First question:\n";
print_r($data[0] ?? []);
