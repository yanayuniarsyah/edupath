<?php
require 'api/config.php';
function api_request($endpoint, $method = 'GET', $token = null) {
    $ch = curl_init();
    $url = "http://localhost:8000/" . ltrim($endpoint, '/');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [];
    if ($token) { $headers[] = "Authorization: Bearer $token"; }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        $headers[] = "Content-Type: application/json";
    }
    if (!empty($headers)) { curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); }
    $result = curl_exec($ch);
    return json_decode($result, true) ?? $result;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/admin.php?action=login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin@uat.edupath.local', 'password' => 'EduPathAdmin01!2026']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$login = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $login['token'];
echo "Tenant Admin Token: " . substr($token, 0, 10) . "...\n";

$res = api_request('admin.php?action=questions', 'GET', $token);
echo "Questions count: " . (is_array($res) ? count($res) : json_encode($res)) . "\n";
