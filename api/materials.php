<?php
// api/materials.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'EntitlementService.php';

$action = $_GET['action'] ?? '';

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. AUTHENTICATE
    $payload = authenticate();
    
    // 2. INITIALIZE ENTITLEMENT SERVICE
    $entitlementService = new EntitlementService($pdo);
    
    // 3. CHECK ENTITLEMENT (Server-side Role & Subscription Enforcement)
    $subtes = safe_str($_GET['subtes'] ?? '');
    $isAdvanced = isset($_GET['advanced']) && $_GET['advanced'] == 1;

    $isStaff = in_array($payload->role, ['superadmin', 'admin', 'teacher', 'school_bk'], true);
    $hasPremiumAccess = $isStaff || $entitlementService->hasEntitlement($payload->id, $payload->tenant_id ?? '', 'premium_materials');

    if ($isAdvanced && !$hasPremiumAccess) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error" => [
                "code" => "FORBIDDEN_ENTITLEMENT",
                "message" => "Anda tidak memiliki akses ke materi premium ini. Silakan upgrade paket Anda."
            ]
        ]);
        exit;
    }
    
    // 4. FETCH RESOURCE
    $test_component = safe_str($_GET['test_component'] ?? '');
    $topic = safe_str($_GET['topic'] ?? '');

    $education_level = safe_str($_GET['education_level'] ?? '');
    $assessment_type = safe_str($_GET['assessment_type'] ?? '');

    $sql = "SELECT id, exam, test_component, subtest, topic, subtopic, skill, indicator, title, content, file_url, teacher_name, education_level, assessment_type, subject, domain, created_at FROM materials WHERE (is_active = 1 OR is_active IS NULL)";
    $params = [];
    if ($subtes) {
        $sql .= " AND (subtest = ? OR subtest LIKE ?)";
        $params[] = $subtes;
        $params[] = "%$subtes%";
    }
    if ($test_component && $test_component !== 'all') {
        $sql .= " AND test_component = ?";
        $params[] = $test_component;
    }
    if ($topic && $topic !== 'all') {
        $sql .= " AND topic = ?";
        $params[] = $topic;
    }
    if ($education_level && $education_level !== 'all') {
        $sql .= " AND (education_level = ? OR education_level IS NULL)";
        $params[] = $education_level;
    }
    if ($assessment_type && $assessment_type !== 'all') {
        $sql .= " AND (assessment_type = ? OR assessment_type IS NULL)";
        $params[] = $assessment_type;
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // 5. STANDARD RESPONSE (Direct array for backward compatibility and test suite)
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($materials);
} else {
    http_response_code(404);
    echo json_encode([
        "success" => false, 
        "error" => [
            "code" => "NOT_FOUND",
            "message" => "Endpoint materi tidak ditemukan"
        ]
    ]);
}
