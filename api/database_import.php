<?php
// api/database_import.php
require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json');

$payload = authenticate($pdo);
if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!in_array($payload->role, ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Hanya administrator yang diizinkan untuk melakukan import database."]);
    exit;
}

$tenant_id = $payload->tenant_id;
if ($payload->role === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenant_id = $_GET['tenant_id'];
}

$action = $_GET['action'] ?? '';

if ($action === 'preview' || $action === 'commit') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['metadata']) || !isset($input['schema_data'])) {
        http_response_code(400);
        echo json_encode(["error" => "Format file import tidak valid. Gunakan file hasil export dari sistem ini."]);
        exit;
    }

    $metadata = $input['metadata'];
    $data = $input['schema_data'];

    // Tenant Safety Check
    if ($payload->role !== 'superadmin' && $metadata['tenant_scope'] !== $tenant_id) {
        http_response_code(403);
        echo json_encode(["error" => "Cross-tenant import violation. Anda tidak dapat mengimpor data tenant lain."]);
        exit;
    }

    $allowed_tables = ['students', 'plans', 'orders', 'invoices', 'payments', 'subscriptions', 'entitlements', 'question_imports_staging'];

    // 1. Validate Schema
    $errors = [];
    foreach ($data as $table_name => $rows) {
        if (!in_array($table_name, $allowed_tables)) {
            $errors[] = "Tabel tidak diizinkan: $table_name";
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(["error" => "Schema Validation Failed", "details" => $errors]);
        exit;
    }

    // 2. Dry Run / Preview
    if ($action === 'preview') {
        $preview = [];
        foreach ($data as $table_name => $rows) {
            $preview[$table_name] = count($rows);
        }
        echo json_encode([
            "success" => true,
            "message" => "Preview berhasil, data siap di-commit.",
            "record_counts" => $preview,
            "metadata" => $metadata
        ]);
        exit;
    }

    // 3. Commit Transaction
    try {
        $pdo->beginTransaction();

        $mkuuid = function(): string {
            $b = bin2hex(random_bytes(16));
            return substr($b,0,8).'-'.substr($b,8,4).'-'.substr($b,12,4).'-'.substr($b,16,4).'-'.substr($b,20,12);
        };

        // We will do REPLACE INTO for safety, or INSERT IGNORE, but REPLACE might break FK if not careful.
        // Actually, INSERT IGNORE is safer for preserving existing data, but the directive says "rollback on failure".
        // Let's use INSERT with ON DUPLICATE KEY UPDATE.
        
        foreach ($data as $table_name => $rows) {
            if (empty($rows)) continue;
            
            $columns = array_keys($rows[0]);
            $col_str = implode(', ', $columns);
            $val_str = implode(', ', array_fill(0, count($columns), '?'));
            
            $update_parts = [];
            foreach ($columns as $col) {
                if ($col !== 'id') {
                    $update_parts[] = "$col = VALUES($col)";
                }
            }
            $update_str = implode(', ', $update_parts);

            // Tenant Safety check for data rows
            if ($payload->role !== 'superadmin') {
                foreach ($rows as $row) {
                    if (isset($row['tenant_id']) && $row['tenant_id'] !== $tenant_id) {
                        throw new Exception("Data row contains unauthorized tenant_id in table $table_name");
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO $table_name ($col_str) VALUES ($val_str) ON DUPLICATE KEY UPDATE $update_str");
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $row[$col];
                }
                $stmt->execute($values);
            }
        }

        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Import berhasil dikommit secara atomic."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Import gagal, seluruh transaksi di-rollback. Error: " . $e->getMessage()]);
    }

} else {
    http_response_code(400);
    echo json_encode(["error" => "Aksi tidak valid (gunakan preview atau commit)"]);
}
?>
