<?php
// api/database_export.php
require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json');

$payload = authenticate($pdo);
if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Check privilege
if (!in_array($payload->role, ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Hanya administrator yang diizinkan untuk melakukan export database."]);
    exit;
}

$tenant_id = $payload->tenant_id;
if ($payload->role === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenant_id = $_GET['tenant_id'];
}

$scope = $_GET['scope'] ?? 'tenant';
if ($scope === 'all' && $payload->role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(["error" => "Hanya superadmin yang dapat melakukan export cross-tenant."]);
    exit;
}

try {
    // Audit log
    $mkuuid = function(): string {
        $b = bin2hex(random_bytes(16));
        return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
    };
    
    // We export relevant tenant data
    $tables = [
        'students' => 'tenant_id',
        'plans' => 'tenant_id',
        'orders' => 'tenant_id',
        'invoices' => 'tenant_id',
        'payments' => 'tenant_id',
        'subscriptions' => 'tenant_id',
        'entitlements' => 'tenant_id'
    ];

    $export_data = [
        "metadata" => [
            "export_time" => date('c'),
            "exported_by" => $payload->id,
            "tenant_scope" => ($scope === 'all') ? "ALL" : $tenant_id,
            "version" => "1.0"
        ],
        "schema_data" => []
    ];

    foreach ($tables as $table => $tenant_col) {
        if ($scope === 'all') {
            $stmt = $pdo->query("SELECT * FROM $table");
            $export_data["schema_data"][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE $tenant_col = ?");
            $stmt->execute([$tenant_id]);
            $export_data["schema_data"][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Also export questions if they belong to tenant (question_imports_staging)
    if ($scope === 'all') {
        $stmt = $pdo->query("SELECT * FROM question_imports_staging");
        $export_data["schema_data"]["question_imports_staging"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM question_imports_staging WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $export_data["schema_data"]["question_imports_staging"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Write to file or return as JSON download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="export_' . ($scope === 'all' ? 'all' : $tenant_id) . '_' . date('Ymd_His') . '.json"');
    echo json_encode($export_data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Export gagal: " . $e->getMessage()]);
}
?>
