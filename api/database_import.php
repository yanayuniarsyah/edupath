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
    $allowed_columns = [
        'students' => ['id','user_id','tenant_id','referred_by','name','email','phone','school','plan','is_active'],
        'plans' => ['id','tenant_id','product_id','name','price','discount','duration','features','billing_cycle'],
        'orders' => ['id','tenant_id','plan_id','order_id','student_id','affiliate_id','plan_name','amount','status','snap_token','billing_cycle_snapshot','paid_at'],
        'invoices' => ['id','tenant_id','student_id','order_id','subtotal','discount','tax_amount','grand_total','currency','historical_pricing_snapshot'],
        'payments' => ['id','tenant_id','invoice_id','order_id','gateway','gateway_transaction_id','payment_method','amount','currency','status'],
        'subscriptions' => ['id','tenant_id','student_id','plan_id','status','payment_reference','plan_name_snapshot','started_at','expires_at'],
        'entitlements' => ['id','tenant_id','student_id','feature_key','subscription_id','expires_at','revoked_at'],
        'question_imports_staging' => ['id','tenant_id','batch_id','row_number','payload','status','error_message']
    ];

    // 1. Validate Schema
    $errors = [];
    foreach ($data as $table_name => $rows) {
        if (!in_array($table_name, $allowed_tables)) {
            $errors[] = "Tabel tidak diizinkan: $table_name";
            continue;
        }
        if (!is_array($rows)) {
            $errors[] = "Data tabel tidak valid: $table_name";
            continue;
        }
        if (!empty($rows)) {
            $invalid = array_diff(array_keys($rows[0]), $allowed_columns[$table_name]);
            if ($invalid) {
                $errors[] = "Kolom tidak diizinkan pada $table_name: " . implode(', ', $invalid);
            }
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
            if (empty($columns) || array_diff($columns, $allowed_columns[$table_name])) {
                throw new Exception("Kolom import tidak diizinkan untuk $table_name");
            }
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
