<?php
require 'test_online_login.php';

echo "\n=== DIAGNOSING ALL ADMIN DASHBOARD ENDPOINTS ===\n";

$endpoints = [
    'stats' => '/api/admin.php?action=stats',
    'students' => '/api/admin.php?action=students',
    'orders' => '/api/admin.php?action=orders&page=1&status=&student_id=&search=',
    'plans' => '/api/admin.php?action=plans',
    'staff' => '/api/admin.php?action=staff',
    'questions' => '/api/admin.php?action=questions&page=1&subtes=',
    'materials' => '/api/admin.php?action=materials',
    'tenants' => '/api/admin.php?action=tenants',
    'entitlements_dict' => '/api/admin.php?action=entitlements_dictionary',
];

foreach ($endpoints as $name => $ep) {
    $res = api_call("https://edupath.co.id$ep", 'GET', $token);
    echo "Endpoint [$name] ($ep):\n";
    echo "  -> HTTP Code: {$res['code']}\n";
    if ($res['code'] !== 200) {
        echo "  -> ERROR BODY: " . json_encode($res['body']) . "\n";
    }
}
