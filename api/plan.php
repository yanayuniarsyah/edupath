<?php
// api/plan.php
// Central Shared Product Catalog & Plan Management (PRD v1.1 - Phase 1)
// Enforces AC-01 (One Central Catalog) and AC-02 (Tenant cannot alter master catalog without central authorization)

require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json');

$payload = authenticate($pdo);
if (!$payload || !in_array($payload->role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Hanya administrator yang memiliki wewenang mengelola katalog paket.']]);
    exit;
}

$is_superadmin = ($payload->role === 'superadmin');
$tenant_id = $payload->tenant_id;
if ($is_superadmin && isset($_GET['tenant_id'])) {
    $tenant_id = $_GET['tenant_id'];
}

$action = $_GET['action'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

switch ($action) {
    case 'list':
        // AC-01: List includes both Central Master Catalog (tenant_id IS NULL) and tenant-specific plans
        if ($is_superadmin && empty($tenant_id)) {
            $stmt = $pdo->prepare('
                SELECT p.*, ap.name as program_name, t.name as tenant_name 
                FROM plans p
                LEFT JOIN academic_programs ap ON p.academic_program = ap.code OR p.program_id = ap.id
                LEFT JOIN tenants t ON p.tenant_id = t.id
                ORDER BY p.is_archived ASC, p.is_active DESC, p.created_at DESC
            ');
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare('
                SELECT p.*, ap.name as program_name,
                       tpo.override_price, tpo.status as override_status, tpo.reason as override_reason
                FROM plans p
                LEFT JOIN academic_programs ap ON p.academic_program = ap.code OR p.program_id = ap.id
                LEFT JOIN tenant_product_overrides tpo ON p.id = tpo.plan_id AND tpo.tenant_id = ? AND tpo.status = "active"
                WHERE p.tenant_id = ? OR p.tenant_id IS NULL
                ORDER BY p.is_archived ASC, p.is_active DESC, p.price ASC
            ');
            $stmt->execute([$tenant_id, $tenant_id]);
        }

        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plans as &$plan) {
            if (!empty($plan['features']) && is_string($plan['features'])) {
                $plan['features'] = json_decode($plan['features'], true);
            }
            if (!empty($plan['components']) && is_string($plan['components'])) {
                $plan['components'] = json_decode($plan['components'], true);
            }
            $plan['is_central_catalog'] = is_null($plan['tenant_id']);
        }
        echo json_encode(['success' => true, 'data' => $plans]);
        break;

    case 'detail':
        $id = $_GET['id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ? AND (tenant_id = ? OR tenant_id IS NULL)');
        $stmt->execute([$id, $tenant_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($plan) {
            if (!empty($plan['features']) && is_string($plan['features'])) $plan['features'] = json_decode($plan['features'], true);
            if (!empty($plan['components']) && is_string($plan['components'])) $plan['components'] = json_decode($plan['components'], true);
            $plan['is_central_catalog'] = is_null($plan['tenant_id']);
            echo json_encode(['success' => true, 'data' => $plan]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Paket tidak ditemukan.']]);
        }
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            http_response_code(405); 
            echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Method not allowed']]); 
            exit; 
        }

        $name = safe_str($input['name'] ?? '');
        $price = $input['price'] ?? 0;
        $discount = $input['discount'] ?? 0;
        $duration = (int)($input['duration'] ?? 30);
        $academic_program = safe_str($input['academic_program'] ?? 'SNPMB/UTBK');
        $delivery_mode = safe_str($input['delivery_mode'] ?? 'online');
        $package_code = safe_str($input['package_code'] ?? '');
        $features = isset($input['features']) ? json_encode($input['features']) : null;
        $components = isset($input['components']) ? json_encode($input['components']) : null;

        // AC-02: Target tenant scope. Only superadmin can create central packages (tenant_id = null)
        $target_tenant = null;
        if (!$is_superadmin) {
            if (empty($tenant_id)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin cabang wajib terikat pada tenant_id.']]);
                exit;
            }
            $target_tenant = $tenant_id;
        } else {
            $target_tenant = !empty($input['tenant_id']) ? $input['tenant_id'] : null;
        }

        if (empty($name) || !is_numeric($price)) { 
            http_response_code(400); 
            echo json_encode(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Nama paket dan harga dasar wajib diisi.']]); 
            exit; 
        }

        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

        try {
            $stmt = $pdo->prepare('
                INSERT INTO plans (
                    id, tenant_id, academic_program, package_code, delivery_mode, 
                    name, price, base_price, discount, duration, duration_value, 
                    features, components, is_active, is_archived, status, version
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, "published", 1)
            ');
            $stmt->execute([
                $id, $target_tenant, $academic_program, $package_code, $delivery_mode,
                $name, $price, $price, $discount, $duration, $duration,
                $features, $components
            ]);

            log_audit($pdo, $payload->id, $target_tenant, 'catalog_package_created', $id, [
                'name' => $name, 'price' => $price, 'mode' => $delivery_mode, 'program' => $academic_program
            ]);

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Paket katalog berhasil dibuat.']);
        } catch (PDOException $e) { 
            http_response_code(500); 
            echo json_encode(['success' => false, 'error' => ['code' => 'DB_ERROR', 'message' => 'Gagal membuat paket: ' . $e->getMessage()]]); 
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = safe_str($input['id'] ?? '');
        $name = safe_str($input['name'] ?? '');
        $price = $input['price'] ?? null;
        $discount = $input['discount'] ?? 0;
        $duration = (int)($input['duration'] ?? 30);
        $academic_program = safe_str($input['academic_program'] ?? 'SNPMB/UTBK');
        $delivery_mode = safe_str($input['delivery_mode'] ?? 'online');
        $package_code = safe_str($input['package_code'] ?? '');
        $features = isset($input['features']) ? json_encode($input['features']) : null;
        $components = isset($input['components']) ? json_encode($input['components']) : null;

        if (empty($id) || empty($name) || $price === null) { 
            http_response_code(400); 
            echo json_encode(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'ID, nama, dan harga wajib diisi.']]); 
            exit; 
        }

        // AC-02 Check: If plan is central catalog (tenant_id IS NULL), only Superadmin can update it
        $check = $pdo->prepare('SELECT tenant_id FROM plans WHERE id = ?');
        $check->execute([$id]);
        $existing = $check->fetch();
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Paket tidak ditemukan.']]);
            exit;
        }

        if (is_null($existing['tenant_id']) && !$is_superadmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['code' => 'AC02_FORBIDDEN', 'message' => 'Tenant/cabang tidak dapat mengubah master katalog pusat tanpa izin pusat.']]);
            exit;
        }

        if (!$is_superadmin && $existing['tenant_id'] !== $tenant_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Anda tidak berhak mengubah paket tenant lain.']]);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                UPDATE plans 
                SET name = ?, price = ?, base_price = ?, discount = ?, duration = ?, duration_value = ?,
                    academic_program = ?, delivery_mode = ?, package_code = ?, features = ?, components = ?,
                    version = version + 1
                WHERE id = ?
            ');
            $stmt->execute([
                $name, $price, $price, $discount, $duration, $duration,
                $academic_program, $delivery_mode, $package_code, $features, $components,
                $id
            ]);

            log_audit($pdo, $payload->id, $existing['tenant_id'], 'catalog_package_updated', $id, [
                'name' => $name, 'price' => $price
            ]);

            echo json_encode(['success' => true, 'message' => 'Paket katalog berhasil diperbarui.']);
        } catch (PDOException $e) { 
            http_response_code(500); 
            echo json_encode(['success' => false, 'error' => ['code' => 'DB_ERROR', 'message' => 'Gagal update: ' . $e->getMessage()]]); 
        }
        break;

    case 'override_price':
        // Mechanism for Tenant Pricing Override (B1 Rule: explicitly authorized without mutating base price)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $plan_id = safe_str($input['plan_id'] ?? '');
        $override_price = $input['override_price'] ?? null;
        $reason = safe_str($input['reason'] ?? '');
        $target_tenant = $is_superadmin ? safe_str($input['tenant_id'] ?? $tenant_id) : $tenant_id;

        if (empty($plan_id) || $override_price === null || empty($reason) || empty($target_tenant)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'plan_id, override_price, tenant_id, dan reason wajib diisi.']]);
            exit;
        }

        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

        try {
            $stmt = $pdo->prepare('
                INSERT INTO tenant_product_overrides (id, tenant_id, plan_id, override_price, reason, authorized_by, status)
                VALUES (?, ?, ?, ?, ?, ?, "active")
                ON DUPLICATE KEY UPDATE override_price = VALUES(override_price), reason = VALUES(reason), authorized_by = VALUES(authorized_by), status = "active", updated_at = NOW()
            ');
            $stmt->execute([$id, $target_tenant, $plan_id, $override_price, $reason, $payload->id]);

            log_audit($pdo, $payload->id, $target_tenant, 'catalog_price_override_set', $plan_id, [
                'override_price' => $override_price, 'reason' => $reason
            ]);

            echo json_encode(['success' => true, 'message' => 'Harga override tenant berhasil disimpan tanpa mengubah harga dasar katalog pusat.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => ['code' => 'DB_ERROR', 'message' => 'Gagal menyimpan override: ' . $e->getMessage()]]);
        }
        break;

    case 'activate':
    case 'deactivate':
    case 'archive':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = safe_str($input['id'] ?? '');
        $status_field = ($action === 'activate') ? 'is_active = 1' : (($action === 'deactivate') ? 'is_active = 0' : 'is_archived = 1, is_active = 0');

        // Check ownership
        $stmt_chk = $pdo->prepare('SELECT tenant_id FROM plans WHERE id = ?');
        $stmt_chk->execute([$id]);
        $row = $stmt_chk->fetch();
        if (!$row) { http_response_code(404); exit; }

        if (is_null($row['tenant_id']) && !$is_superadmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => ['code' => 'AC02_FORBIDDEN', 'message' => 'Hanya superadmin yang dapat mengubah status paket katalog pusat.']]);
            exit;
        }

        $pdo->prepare("UPDATE plans SET $status_field WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400); 
        echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'Action tidak valid.']]); 
        break;
}
