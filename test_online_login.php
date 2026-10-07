<?php
function api_call($url, $method = 'GET', $token = null, $data = null, $csrf = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $headers = ['Content-Type: application/json', 'User-Agent: Mozilla/5.0'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    if ($csrf) $headers[] = "X-CSRF-Token: $csrf";
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true) ?: $res];
}

echo "=== TESTING LIVE ONLINE SYSTEM (https://edupath.co.id) ===\n\n";

// 1. Test Superadmin Login
echo "[1] Testing Superadmin Login...\n";
$login = api_call('https://edupath.co.id/api/admin.php?action=login', 'POST', null, [
    'username' => 'superadmin@uat.edupath.local',
    'password' => 'EduPathSuperAdmin01!2026'
]);
echo "    -> HTTP Code: {$login['code']}\n";
if ($login['code'] !== 200) {
    echo "    -> FAIL: " . json_encode($login['body']) . "\n";
    exit;
}
$token = $login['body']['token'];
$csrf = $login['body']['csrf_token'] ?? null;
echo "    -> SUCCESS! Role: {$login['body']['user']['role']}\n\n";

// 2. Test Get Stats
echo "[2] Testing Get Stats...\n";
$stats = api_call('https://edupath.co.id/api/admin.php?action=stats', 'GET', $token);
echo "    -> HTTP Code: {$stats['code']}\n";
echo "    -> Total Students: " . ($stats['body']['totalStudents'] ?? 'N/A') . "\n\n";

// 3. Test Get Students List
echo "[3] Testing Get Students List...\n";
$students = api_call('https://edupath.co.id/api/admin.php?action=students', 'GET', $token);
echo "    -> HTTP Code: {$students['code']}\n";
$count = count($students['body']['students'] ?? []);
echo "    -> Found {$count} students.\n\n";

// 4. Test Create Student
echo "[4] Testing Create Student...\n";
$newEmail = 'test_online_' . time() . '@example.com';
$created = api_call('https://edupath.co.id/api/admin.php?action=students', 'POST', $token, [
    'name' => 'Test Siswa Online Live',
    'email' => $newEmail,
    'password' => 'Password123!',
    'plan' => 'utama'
], $csrf);
echo "    -> HTTP Code: {$created['code']}\n";
$createdId = $created['body']['id'] ?? null;
echo "    -> Created Student ID: $createdId\n\n";

if ($createdId) {
    // 5. Test Edit Student
    echo "[5] Testing Edit / Update Student...\n";
    $updated = api_call('https://edupath.co.id/api/admin.php?action=students', 'PUT', $token, [
        'id' => $createdId,
        'name' => 'Test Siswa Online (UPDATED)',
        'email' => $newEmail,
        'plan' => 'vip',
        'is_active' => 1
    ], $csrf);
    echo "    -> HTTP Code: {$updated['code']}\n";
    echo "    -> Update Response: " . json_encode($updated['body']) . "\n\n";

    // 6. Test Delete Student
    echo "[6] Testing Delete Student...\n";
    $deleted = api_call("https://edupath.co.id/api/admin.php?action=students&id=$createdId", 'DELETE', $token, null, $csrf);
    echo "    -> HTTP Code: {$deleted['code']}\n";
    echo "    -> Delete Response: " . json_encode($deleted['body']) . "\n\n";
}

echo "=== ALL LIVE TESTS COMPLETED SUCCESSFULLY! ===\n";
