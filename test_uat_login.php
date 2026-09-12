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

$res = api_request('admin.php?action=login', 'POST', null, ['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']);
echo "SuperAdmin Login Response:\n";
print_r($res);

$res = api_request('auth.php?action=login', 'POST', null, ['email' => 'student@uat.edupath.local', 'password' => 'EduPathStudent01!2026']);
echo "\nStudent Login Response:\n";
print_r($res);

?>
