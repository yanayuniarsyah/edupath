<?php
// test_all_requirements.php
// Verification script for all 7 user requirements

$baseUrl = 'http://localhost:8000/api';

function callApi($method, $path, $token = null, $body = null) {
    global $baseUrl;
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($response, true), 'raw' => $response];
}

$testsPassed = 0;
$totalTests = 0;

function assertTest($condition, $message) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
    }
}

echo "====================================================\n";
echo "VERIFIKASI 7 REQUIREMENTS SOFTWARE FACTORY\n";
echo "====================================================\n\n";

// --- 1. LOGIN TESTS ---
echo "1. AUTH & ROLE TOKENS:\n";
$superLogin = callApi('POST', '/admin.php?action=login', null, [
    'username' => 'superadmin@uat.edupath.local',
    'password' => 'EduPathSuperAdmin01!2026'
]);
assertTest($superLogin['status'] === 200 && !empty($superLogin['data']['token']), "Superadmin login HTTP 200");
$superToken = $superLogin['data']['token'] ?? '';

$adminLogin = callApi('POST', '/admin.php?action=login', null, [
    'username' => 'admin@uat.edupath.local',
    'password' => 'EduPathAdmin01!2026'
]);
assertTest($adminLogin['status'] === 200 && !empty($adminLogin['data']['token']), "Admin Utama login HTTP 200");
$adminToken = $adminLogin['data']['token'] ?? '';

$tutorLogin = callApi('POST', '/admin.php?action=login', null, [
    'username' => 'tutor@uat.edupath.local',
    'password' => 'EduPathTutor01!2026'
]);
assertTest($tutorLogin['status'] === 200 && !empty($tutorLogin['data']['token']), "Teacher / Tutor login HTTP 200");
$tutorToken = $tutorLogin['data']['token'] ?? '';

// --- 2. DASHBOARD GURU ACCESS ---
echo "\n2. DASHBOARD GURU RBAC & APIS:\n";
$tutorStats = callApi('GET', '/admin.php?action=stats', $tutorToken);
assertTest($tutorStats['status'] === 200 && isset($tutorStats['data']['totalQuizzes']), "Teacher stats accessible (HTTP 200, not 403)");

$tutorMaterials = callApi('GET', '/admin.php?action=materials', $tutorToken);
assertTest($tutorMaterials['status'] === 200, "Teacher materials list accessible (HTTP 200)");

$tutorQuestions = callApi('GET', '/admin.php?action=questions', $tutorToken);
assertTest($tutorQuestions['status'] === 200, "Teacher questions list accessible (HTTP 200)");

$tutorStaffAttempt = callApi('POST', '/admin.php?action=staff', $tutorToken, ['username' => 'dummy', 'password' => 'pass']);
assertTest($tutorStaffAttempt['status'] === 403, "Teacher denied access to staff management (HTTP 403)");

// --- 3. ROLE RESTRICTION ON STAFF MANAGEMENT ---
echo "\n3. ROLE RESTRICTION ON STAFF (HANYA SUPERADMIN):\n";
$adminStaffPost = callApi('POST', '/admin.php?action=staff', $adminToken, [
    'username' => 'illegal_staff@test.local',
    'password' => 'TestPass123!',
    'name' => 'Illegal Staff',
    'role' => 'teacher'
]);
assertTest($adminStaffPost['status'] === 403, "Admin Utama cannot create staff (HTTP 403)");

$adminStaffPut = callApi('PUT', '/admin.php?action=staff', $adminToken, [
    'id' => 'uat-r-teacher',
    'name' => 'Hacked Name'
]);
assertTest($adminStaffPut['status'] === 403, "Admin Utama cannot edit staff (HTTP 403)");

$adminStaffDel = callApi('DELETE', '/admin.php?action=staff&id=uat-r-teacher', $adminToken);
assertTest($adminStaffDel['status'] === 403, "Admin Utama cannot delete staff (HTTP 403)");

// Superadmin can create, edit, delete staff
$superStaffPost = callApi('POST', '/admin.php?action=staff', $superToken, [
    'username' => 'new_tutor_' . time() . '@test.local',
    'password' => 'StaffPass123!@#',
    'name' => 'Tutor Baru Test',
    'role' => 'teacher'
]);
assertTest($superStaffPost['status'] === 200 && !empty($superStaffPost['data']['id']), "Superadmin can create staff (HTTP 200)");
$newStaffId = $superStaffPost['data']['id'] ?? '';

if ($newStaffId) {
    $superStaffPut = callApi('PUT', '/admin.php?action=staff', $superToken, [
        'id' => $newStaffId,
        'name' => 'Tutor Updated'
    ]);
    assertTest($superStaffPut['status'] === 200, "Superadmin can edit staff (HTTP 200)");

    $superStaffDel = callApi('DELETE', "/admin.php?action=staff&id=$newStaffId", $superToken);
    assertTest($superStaffDel['status'] === 200, "Superadmin can delete staff (HTTP 200)");
}

// --- 4. SUPERADMIN CRUD PAKET (PLANS) ---
echo "\n4. SUPERADMIN CRUD PAKET:\n";
$planPost = callApi('POST', '/admin.php?action=plans', $superToken, [
    'name' => 'Paket Test ' . time(),
    'price' => 250000,
    'duration' => 30,
    'features' => ['tryout_unlimited', 'ai_adaptive_path']
]);
assertTest($planPost['status'] === 200 && !empty($planPost['data']['id']), "Superadmin can create plan (HTTP 200)");
$testPlanId = $planPost['data']['id'] ?? '';

if ($testPlanId) {
    $planPut = callApi('PUT', '/admin.php?action=plans', $superToken, [
        'id' => $testPlanId,
        'name' => 'Paket Test Updated',
        'price' => 300000,
        'duration' => 60,
        'features' => ['tryout_unlimited']
    ]);
    assertTest($planPut['status'] === 200, "Superadmin can edit plan (HTTP 200)");

    $planDel = callApi('DELETE', "/admin.php?action=plans&id=$testPlanId", $superToken);
    assertTest($planDel['status'] === 200, "Superadmin can delete plan (HTTP 200, safe FK)");
}

// --- 5. SUPERADMIN CRUD MATERI (MATERIALS) ---
echo "\n5. SUPERADMIN CRUD MATERI:\n";
$matPost = callApi('POST', '/admin.php?action=materials', $superToken, [
    'title' => 'Materi Uji Coba ' . time(),
    'content' => 'Konten uji coba materi pembelajaran',
    'subtest' => 'Penalaran Umum',
    'exam' => 'SNBT',
    'test_component' => 'TPS'
]);
assertTest($matPost['status'] === 200 && !empty($matPost['data']['id']), "Superadmin can create material (HTTP 200)");
$testMatId = $matPost['data']['id'] ?? '';

if ($testMatId) {
    $matPut = callApi('PUT', '/admin.php?action=materials', $superToken, [
        'id' => $testMatId,
        'title' => 'Materi Uji Coba Updated',
        'content' => 'Konten materi terupdate',
        'subtest' => 'Penalaran Umum',
        'exam' => 'SNBT',
        'test_component' => 'TPS'
    ]);
    assertTest($matPut['status'] === 200, "Superadmin can edit material (HTTP 200)");

    $matDel = callApi('DELETE', "/admin.php?action=materials&id=$testMatId", $superToken);
    assertTest($matDel['status'] === 200, "Superadmin can delete material (HTTP 200)");
}

// --- 6. SUPERADMIN CRUD BANK SOAL (QUESTIONS) ---
echo "\n6. SUPERADMIN CRUD BANK SOAL:\n";
$qPost = callApi('POST', '/admin.php?action=questions', $superToken, [
    'exam' => 'SNBT',
    'subtest' => 'Penalaran Umum',
    'question' => 'Berapakah 2 + 2?',
    'option_a' => '4',
    'option_b' => '5',
    'option_c' => '6',
    'option_d' => '7',
    'option_e' => '8',
    'correct' => 'a',
    'difficulty' => 'easy'
]);
assertTest($qPost['status'] === 200 && !empty($qPost['data']['id']), "Superadmin can create question (HTTP 200)");
if ($qPost['status'] !== 200) {
    echo "  [DEBUG qPost] Status: " . $qPost['status'] . " Raw: " . $qPost['raw'] . "\n";
}
$testQId = $qPost['data']['id'] ?? '';

if ($testQId) {
    $qPut = callApi('PUT', '/admin.php?action=questions', $superToken, [
        'id' => $testQId,
        'exam' => 'SNBT',
        'subtest' => 'Penalaran Umum',
        'question' => 'Berapakah 3 + 3?',
        'option_a' => '6',
        'option_b' => '5',
        'option_c' => '4',
        'option_d' => '7',
        'option_e' => '8',
        'correct' => 'a',
        'difficulty' => 'easy'
    ]);
    assertTest($qPut['status'] === 200, "Superadmin can edit question (HTTP 200)");

    $qDel = callApi('DELETE', "/admin.php?action=questions&id=$testQId", $superToken);
    assertTest($qDel['status'] === 200, "Superadmin can delete question (HTTP 200)");
}

// --- 7. SUPERADMIN CRUD SISWA (STUDENTS) ---
echo "\n7. SUPERADMIN CRUD SISWA:\n";
$testStudentEmail = 'student_test_' . time() . '@test.local';
$stuPost = callApi('POST', '/admin.php?action=students', $superToken, [
    'name' => 'Siswa Test CRUD',
    'email' => $testStudentEmail,
    'password' => 'PassSiswa123!',
    'plan' => 'free'
]);
assertTest($stuPost['status'] === 200 && !empty($stuPost['data']['id']), "Superadmin can create student (HTTP 200)");
$testStuId = $stuPost['data']['id'] ?? '';

if ($testStuId) {
    $stuPut = callApi('PUT', '/admin.php?action=students', $superToken, [
        'id' => $testStuId,
        'name' => 'Siswa Test CRUD Updated',
        'email' => $testStudentEmail,
        'plan' => 'utama'
    ]);
    assertTest($stuPut['status'] === 200, "Superadmin can edit student (HTTP 200)");

    $stuDel = callApi('DELETE', "/admin.php?action=students&id=$testStuId", $superToken);
    assertTest($stuDel['status'] === 200, "Superadmin can delete student (HTTP 200, clean cascade)");
}

// --- 8. RIWAYAT TRANSAKSI FILTER PER SISWA ---
echo "\n8. RIWAYAT TRANSAKSI FILTER PER SISWA:\n";
// Create a student and an order
$filterStuEmail = 'student_filter_' . time() . '@test.local';
$stuForOrder = callApi('POST', '/admin.php?action=students', $superToken, [
    'name' => 'Siswa Filter Order',
    'email' => $filterStuEmail,
    'password' => 'PassSiswa123!',
    'plan' => 'free'
]);
$filterStuId = $stuForOrder['data']['id'] ?? '';

if ($filterStuId) {
    $orderCreate = callApi('POST', '/admin.php?action=orders', $superToken, [
        'student_id' => $filterStuId,
        'plan_id' => 'plan-utama',
        'plan_name' => 'Paket Utama',
        'amount' => 450000,
        'status' => 'paid'
    ]);
    assertTest($orderCreate['status'] === 200, "Created test order for student");
    if ($orderCreate['status'] !== 200) {
        echo "  [DEBUG orderCreate] Status: " . $orderCreate['status'] . " Raw: " . $orderCreate['raw'] . "\n";
    }

    // Filter by student_id
    $filterRes = callApi('GET', "/admin.php?action=orders&student_id=$filterStuId", $superToken);
    $ordersReturned = $filterRes['data']['orders'] ?? [];
    $allMatch = count($ordersReturned) > 0;
    foreach ($ordersReturned as $ord) {
        if ($ord['student_id'] !== $filterStuId) {
            $allMatch = false;
        }
    }
    assertTest($filterRes['status'] === 200 && $allMatch, "Filtered orders returned only orders for specified student (count: " . count($ordersReturned) . ")");

    // Filter by search student name
    $searchRes = callApi('GET', "/admin.php?action=orders&search=Siswa+Filter+Order", $superToken);
    $searchOrders = $searchRes['data']['orders'] ?? [];
    assertTest($searchRes['status'] === 200 && count($searchOrders) > 0, "Search orders by student name returned matching records");

    // Cleanup student & order
    callApi('DELETE', "/admin.php?action=students&id=$filterStuId", $superToken);
}

echo "\n====================================================\n";
echo "SUMMARY: $testsPassed / $totalTests TESTS PASSED\n";
echo "====================================================\n";
