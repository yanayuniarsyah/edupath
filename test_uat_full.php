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

echo "=== FULL UAT WORKFLOW TEST ===\n\n";

// 1. Super Admin Test
echo "[1] Logging in as Super Admin...\n";
$sa_login = api_request('admin.php?action=login', 'POST', null, ['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']);
if ($sa_login['code'] === 200) {
    $sa_token = $sa_login['body']['token'];
    echo "    -> SUCCESS. Got token.\n";
    echo "    -> Fetching tenants list (Superadmin only endpoint)...\n";
    $tenants = api_request('admin.php?action=tenants', 'GET', $sa_token);
    echo "    -> Response Code: " . $tenants['code'] . "\n";
    echo "    -> Data Count: " . count($tenants['body']) . " tenants found.\n\n";
} else {
    echo "    -> FAILED: " . json_encode($sa_login) . "\n\n";
}

// 2. Tenant Admin Test
echo "[2] Logging in as Tenant Admin...\n";
$adm_login = api_request('admin.php?action=login', 'POST', null, ['username' => 'admin@uat.edupath.local', 'password' => 'EduPathAdmin01!2026']);
if ($adm_login['code'] === 200) {
    $adm_token = $adm_login['body']['token'];
    echo "    -> SUCCESS. Got token.\n";
    
    echo "    -> Testing Tenant Boundary (Trying to fetch all tenants)...\n";
    $adm_tenants = api_request('admin.php?action=tenants', 'GET', $adm_token);
    echo "    -> Boundary check response: " . $adm_tenants['code'] . " (Expected: 403 Forbidden)\n";
    
    echo "    -> Fetching student list for this tenant...\n";
    $students = api_request('admin.php?action=students', 'GET', $adm_token);
    echo "    -> Response Code: " . $students['code'] . "\n";
    echo "    -> Data Count: " . count($students['body']) . " students found.\n\n";
} else {
    echo "    -> FAILED: " . json_encode($adm_login) . "\n\n";
}

// 3. Student Test
echo "[3] Logging in as Student...\n";
$stu_login = api_request('auth.php?action=login', 'POST', null, ['email' => 'student@uat.edupath.local', 'password' => 'EduPathStudent01!2026']);
if ($stu_login['code'] === 200) {
    $stu_token = $stu_login['body']['token'];
    echo "    -> SUCCESS. Got JWT & Refresh Token.\n";
    
    echo "    -> Fetching profile (/auth.php?action=me)...\n";
    $profile = api_request('auth.php?action=me', 'GET', $stu_token);
    echo "    -> Profile Name: " . ($profile['body']['user']['name'] ?? 'UNKNOWN') . "\n";
    
    echo "    -> Testing RBAC Boundary (Trying to access admin endpoints)...\n";
    $stu_admin_test = api_request('admin.php?action=students', 'GET', $stu_token);
    echo "    -> Boundary check response: " . $stu_admin_test['code'] . " (Expected: 403 Forbidden)\n";
} else {
    echo "    -> FAILED: " . json_encode($stu_login) . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
