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
    if ($subtes) {
        $stmt = $pdo->prepare("SELECT id, subtes, title, content FROM materials WHERE subtes = ?");
        $stmt->execute([$subtes]);
    } else {
        $stmt = $pdo->query("SELECT id, subtes, title, content FROM materials");
    }
    
    // 5. STANDARD RESPONSE
    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll()
    ]);
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
