<?php
require 'api/config.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/admin.php?action=login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$token = json_decode(curl_exec($ch), true)['token'];

curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/admin.php?action=questions&page=1&subtes=");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_POST, false);
echo curl_exec($ch);
