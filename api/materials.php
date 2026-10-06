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
    
    // 3. CHECK ENTITLEMENT
    // Contoh: Jika user ingin akses materi advanced/premium
    $subtes = $_GET['subtes'] ?? '';
    $isAdvanced = isset($_GET['advanced']) && $_GET['advanced'] == 1;

    if ($isAdvanced) {
        if (!$entitlementService->hasEntitlement($payload->id, $payload->tenant_id, 'premium_materials')) {
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
    }
    
    // 4. FETCH RESOURCE
    $test_component = $_GET['test_component'] ?? '';
    $topic = $_GET['topic'] ?? '';

    $sql = "SELECT id, exam, test_component, subtest, topic, subtopic, skill, indicator, title, content, teacher_name, created_at FROM materials WHERE 1=1";
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
?>
