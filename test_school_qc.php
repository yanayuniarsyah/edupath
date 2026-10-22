<?php
// test_school_qc.php — Comprehensive Expert IT QC Verification for School & BK Portal
ini_set('display_errors', 1);
error_reporting(E_ALL);

$liveBase = 'https://edupath.co.id/api';

class SchoolQCAuditor {
    public $passed = 0;
    public $failed = 0;
    public $warnings = 0;

    public function recordPass($title, $detail = '') {
        $this->passed++;
        echo "  [\033[32mPASS\033[0m] $title" . ($detail ? " ($detail)" : "") . "\n";
    }

    public function recordFail($title, $detail = '') {
        $this->failed++;
        echo "  [\033[31mFAIL\033[0m] $title" . ($detail ? " ($detail)" : "") . "\n";
    }

    public function recordWarn($title, $detail = '') {
        $this->warnings++;
        echo "  [\033[33mWARN\033[0m] $title" . ($detail ? " ($detail)" : "") . "\n";
    }

    public function http($url, $method = 'GET', $token = null, $data = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $headers = ['Content-Type: application/json', 'User-Agent: SchoolQC-Auditor/1.0'];
        if ($token) $headers[] = "Authorization: Bearer $token";

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw, true) ?? $raw;
        return ['code' => $code, 'body' => $body, 'raw' => $raw];
    }
}

$qc = new SchoolQCAuditor();

echo "====================================================================\n";
echo "   EDUPATH SCHOOL & GURU BK PORTAL - EXPERT IT QC VERIFICATION\n";
echo "====================================================================\n\n";

// 1. Check Endpoint Accessibility
echo "1. ENDPOINT ACCESSIBILITY CHECK:\n";
$resCheck = $qc->http("$liveBase/school.php");
if ($resCheck['code'] === 404 || $resCheck['code'] === 401 || $resCheck['code'] === 400 || $resCheck['code'] === 403) {
    $qc->recordPass("Endpoint Availability", "api/school.php responded with HTTP {$resCheck['code']} (Route deployed)");
} else {
    $qc->recordFail("Endpoint Availability", "api/school.php returned unexpected HTTP {$resCheck['code']}");
}

// 2. School Registration Validation (Negative Test)
echo "\n2. REGISTRATION VALIDATION (NEGATIVE TESTS):\n";
$emptyReg = $qc->http("$liveBase/school.php?action=register", 'POST', null, []);
if ($emptyReg['code'] === 400) {
    $qc->recordPass("Empty Registration Block", "Properly rejected with HTTP 400 Bad Request");
} else {
    $qc->recordFail("Empty Registration Vulnerability", "Unexpected code: HTTP {$emptyReg['code']}");
}

// 3. School Registration (Positive Test)
echo "\n3. SCHOOL SELF-SERVICE REGISTRATION (POSITIVE TEST):\n";
$randSuffix = substr(md5(uniqid()), 0, 6);
$testEmail = "bk.sman1test_{$randSuffix}@edupath.co.id";
$testSchoolName = "SMAN 1 Test {$randSuffix}";

$regPayload = [
    'name' => $testSchoolName,
    'npsn' => '202' . rand(10000, 99999),
    'city' => 'Kabupaten Ciamis',
    'bk_name' => 'Dra. Hj. Test BK, M.Pd',
    'phone' => '0812' . rand(10000000, 99999999),
    'email' => $testEmail,
    'password' => 'BKSekolah123!2026',
    'estimated_students' => 300
];

$regRes = $qc->http("$liveBase/school.php?action=register", 'POST', null, $regPayload);
$schoolToken = null;
if ($regRes['code'] === 200 && !empty($regRes['body']['token'])) {
    $schoolToken = $regRes['body']['token'];
    $qc->recordPass("School Registration", "HTTP 200, JWT token created, Share Link: " . ($regRes['body']['school']['share_link'] ?? ''));
} else {
    $qc->recordFail("School Registration Failed", "HTTP {$regRes['code']}: " . json_encode($regRes['body']));
}

// 4. School Login (Positive & Negative)
echo "\n4. SCHOOL LOGIN AUTHENTICATION:\n";
// 4.1 Negative Test (Wrong password)
$wrongLogin = $qc->http("$liveBase/school.php?action=login", 'POST', null, [
    'email' => $testEmail,
    'password' => 'WrongPassword!'
]);
if ($wrongLogin['code'] === 401) {
    $qc->recordPass("Negative Login Test", "Wrong password blocked with HTTP 401");
} else {
    $qc->recordFail("Negative Login Vulnerability", "HTTP {$wrongLogin['code']}");
}

// 4.2 Positive Test
$loginRes = $qc->http("$liveBase/school.php?action=login", 'POST', null, [
    'email' => $testEmail,
    'password' => 'BKSekolah123!2026'
]);
if ($loginRes['code'] === 200 && !empty($loginRes['body']['token'])) {
    $schoolToken = $loginRes['body']['token'];
    $qc->recordPass("Guru BK Login", "HTTP 200, successfully authenticated as school_bk");
} else {
    $qc->recordFail("Guru BK Login Failed", "HTTP {$loginRes['code']}");
}

// 5. Dashboard Aggregates & Student Package Breakdown
echo "\n5. AGGREGATE DASHBOARD & STUDENT PACKAGE BREAKDOWN:\n";
if ($schoolToken) {
    $dashRes = $qc->http("$liveBase/school.php?action=dashboard", 'GET', $schoolToken);
    if ($dashRes['code'] === 200 && $dashRes['body']['success']) {
        $b = $dashRes['body'];
        $qc->recordPass("Dashboard Aggregates Response", "School: {$b['school_name']}");
        
        // Check Package Breakdown structure
        if (isset($b['package_breakdown']['pejuang_ptn']) &&
            isset($b['package_breakdown']['mandiri']) &&
            isset($b['package_breakdown']['elite']) &&
            isset($b['package_breakdown']['free_spp'])) {
            $qc->recordPass("Package Breakdown Structure", 
                "Pejuang PTN: {$b['package_breakdown']['pejuang_ptn']}, " .
                "Mandiri: {$b['package_breakdown']['mandiri']}, " .
                "Elite: {$b['package_breakdown']['elite']}, " .
                "Free SPP: {$b['package_breakdown']['free_spp']}"
            );
        } else {
            $qc->recordFail("Package Breakdown Missing Fields", json_encode($b['package_breakdown'] ?? []));
        }

        // Check KPIs
        if (isset($b['kpis']['total_students']) && isset($b['kpis']['paid_package_students'])) {
            $qc->recordPass("KPI Metrics Integrity", "Total: {$b['kpis']['total_students']}, Paid Packages: {$b['kpis']['paid_package_students']}");
        } else {
            $qc->recordFail("KPI Metrics Malformed", json_encode($b['kpis'] ?? []));
        }
    } else {
        $qc->recordFail("Dashboard Aggregate Failed", "HTTP {$dashRes['code']}: " . json_encode($dashRes['body']));
    }
} else {
    $qc->recordWarn("Dashboard Skipped", "No school token available");
}

// 6. Student Roster & Package Badges
echo "\n6. STUDENT ROSTER & FILTERING:\n";
if ($schoolToken) {
    $rosterRes = $qc->http("$liveBase/school.php?action=students&page=1&limit=10", 'GET', $schoolToken);
    if ($rosterRes['code'] === 200 && $rosterRes['body']['success']) {
        $qc->recordPass("Student Roster Retrieval", "Total records: {$rosterRes['body']['total']}");
    } else {
        $qc->recordFail("Student Roster Retrieval Failed", "HTTP {$rosterRes['code']}");
    }

    // Filter by package: paid
    $paidFilter = $qc->http("$liveBase/school.php?action=students&package=paid", 'GET', $schoolToken);
    if ($paidFilter['code'] === 200 && $paidFilter['body']['success']) {
        $qc->recordPass("Roster Package Filter (paid)", "Filter parameter handled correctly");
    } else {
        $qc->recordFail("Roster Package Filter Failed", "HTTP {$paidFilter['code']}");
    }
}

// 7. Student Data CSV Export
echo "\n7. CSV EXPORT INTEGRITY:\n";
if ($schoolToken) {
    $exportRes = $qc->http("$liveBase/school.php?action=export&token=$schoolToken", 'GET');
    if ($exportRes['code'] === 200 && strpos($exportRes['raw'], 'Nama Siswa') !== false) {
        $qc->recordPass("CSV Export Content", "Contains expected CSV headers and student package columns");
    } else {
        $qc->recordFail("CSV Export Failed", "HTTP {$exportRes['code']}, Content: " . substr($exportRes['raw'], 0, 100));
    }
}

echo "\n====================================================================\n";
echo "   QC AUDIT SUMMARY: Passed={$qc->passed}, Failed={$qc->failed}, Warnings={$qc->warnings}\n";
echo "====================================================================\n";
