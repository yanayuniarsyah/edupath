<?php
function test_login($url, $payload) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return "HTTP $httpcode : $response\n";
}

echo "Testing Superadmin login...\n";
echo test_login("http://localhost:8000/api/admin.php?action=login", [
    "username" => "superadmin@uat.edupath.local",
    "password" => "password123"
]);

echo "\nTesting Demo login...\n";
echo test_login("http://localhost:8000/api/auth.php?action=login", [
    "email" => "demo@edupath.id",
    "password" => "demo123"
]);
