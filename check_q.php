<?php
require 'api/config.php';

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

$stmt = $pdo->query("SELECT COUNT(*) FROM questions");
echo "DB COUNT: " . $stmt->fetchColumn() . "\n";

$sa_login = api_request('admin.php?action=login', 'POST', null, ['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']);
$sa_token = $sa_login['body']['token'] ?? null;

if ($sa_token) {
    $res = api_request('admin.php?action=questions', 'GET', $sa_token);
    echo "API HTTP CODE: " . $res['code'] . "\n";
    if (is_array($res['body'])) {
        echo "API COUNT: " . count($res['body']) . "\n";
    } else {
        echo "API RESPONSE: " . json_encode($res['body']) . "\n";
    }
}
