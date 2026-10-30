<?php
// api/plans.php
// Central Shared Product Catalog Endpoint (PRD v1.1 - Phase 1)
// Fully backward compatible with legacy frontend while providing unified multi-program catalog

require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => ["code" => "METHOD_NOT_ALLOWED", "message" => "Method not allowed"]]);
    exit;
}

try {
    $filter_program = trim($_GET['program'] ?? $_GET['academic_program'] ?? '');
    $filter_mode    = trim($_GET['delivery_mode'] ?? '');
    $tenant_id      = trim($_GET['tenant_id'] ?? '');

    // 1. Fetch active products (grouping headers)
    $products = [];
    try {
        $stmt = $pdo->prepare("
            SELECT id, code, name, description 
            FROM products 
            WHERE status = 'active'
            ORDER BY CASE 
                WHEN code = 'MANDIRI' OR code = 'STARTER' THEN 1
                WHEN code = 'UTBK' OR code = 'UTAMA' THEN 2
                WHEN code = 'VIP' OR code = 'ELITE' THEN 3
                ELSE 4 
            END ASC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $products = [];
    }

    // 2. Fetch active plans with Phase 1 catalog columns (safe column detection)
    $plan_cols = [];
    try {
        $plan_cols = $pdo->query("DESCRIBE plans")->fetchAll(PDO::FETCH_COLUMN);
    } catch (\Throwable $e) {
        $plan_cols = ['id', 'name', 'price', 'duration', 'features'];
    }

    $has_prog_col = in_array('academic_program', $plan_cols);
    $has_mode_col = in_array('delivery_mode', $plan_cols);
    $has_comp_col = in_array('components', $plan_cols);
    $has_curr_col = in_array('currency', $plan_cols);
    $has_ver_col  = in_array('version', $plan_cols);
    $has_code_col = in_array('package_code', $plan_cols);

    $sql = "
        SELECT 
            id, 
            product_id, 
            name, 
            price, 
            discount, 
            duration, 
            features, 
            billing_cycle
    ";
    if ($has_prog_col) $sql .= ", academic_program";
    if ($has_mode_col) $sql .= ", delivery_mode";
    if ($has_comp_col) $sql .= ", components";
    if ($has_curr_col) $sql .= ", currency";
    if ($has_ver_col)  $sql .= ", version";
    if ($has_code_col) $sql .= ", package_code";

    $sql .= " FROM plans WHERE is_active = 1 AND is_archived = 0";
    $params = [];

    if ($filter_program !== '' && $has_prog_col) {
        $sql .= " AND academic_program = ?";
        $params[] = $filter_program;
    }
    if ($filter_mode !== '' && $has_mode_col) {
        $sql .= " AND delivery_mode = ?";
        $params[] = $filter_mode;
    }

    $sql .= " ORDER BY price ASC";
    $stmt_plans = $pdo->prepare($sql);
    $stmt_plans->execute($params);
    $plans = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);

    // 3. Process tenant overrides if tenant_id is provided (PRD v1.1 B1 compliance)
    $overrides = [];
    if (!empty($tenant_id)) {
        try {
            $stmt_ov = $pdo->prepare("
                SELECT plan_id, override_price, currency, reason 
                FROM tenant_product_overrides 
                WHERE tenant_id = ? AND status = 'active'
                  AND (effective_from IS NULL OR effective_from <= NOW())
                  AND (effective_until IS NULL OR effective_until >= NOW())
            ");
            $stmt_ov->execute([$tenant_id]);
            while ($row = $stmt_ov->fetch(PDO::FETCH_ASSOC)) {
                $overrides[$row['plan_id']] = $row;
            }
        } catch (\Throwable $e) {}
    }

    // Format plans
    foreach ($plans as &$plan) {
        if (!isset($plan['academic_program'])) $plan['academic_program'] = 'SNPMB/UTBK';
        if (!isset($plan['delivery_mode']))    $plan['delivery_mode'] = 'online';
        if (!isset($plan['currency']))         $plan['currency'] = 'IDR';
        if (!isset($plan['version']))          $plan['version'] = 1;
        
        // Parse JSON components and features
        if (!empty($plan['features']) && is_string($plan['features'])) {
            $plan['features_parsed'] = json_decode($plan['features'], true);
        }
        if (!empty($plan['components']) && is_string($plan['components'])) {
            $plan['components'] = json_decode($plan['components'], true);
        }

        // Apply tenant price override if exists
        if (isset($overrides[$plan['id']])) {
            $ov = $overrides[$plan['id']];
            $plan['base_price'] = $plan['price'];
            $plan['price'] = $ov['override_price'];
            $plan['has_tenant_override'] = true;
            $plan['override_reason'] = $ov['reason'];
        } else {
            $plan['base_price'] = $plan['price'];
            $plan['has_tenant_override'] = false;
        }
    }

    // Group plans by product_id
    $plans_by_product = [];
    foreach ($plans as $plan) {
        $pid = $plan['product_id'] ?? '';
        if ($pid) {
            $plans_by_product[$pid][] = $plan;
        }
    }

    $response = [];
    foreach ($products as $prod) {
        if (isset($plans_by_product[$prod['id']])) {
            $prod['plans'] = $plans_by_product[$prod['id']];
            $response[] = $prod;
        }
    }

    // Fallback: If products table is empty or unlinked, wrap active plans into standardized products
    if (empty($response) && !empty($plans)) {
        $defaultGroup = [
            'id' => 'prod-utbk',
            'code' => 'UTBK',
            'name' => 'Paket Pejuang PTN & Akademik',
            'description' => 'Akses penuh simulasi UTBK SNBT, materi TKA, dan bimbingan terpadu.',
            'plans' => $plans
        ];
        $response = [$defaultGroup];
    }

    // Return legacy "products" envelope plus unified "packages" and metadata
    echo json_encode([
        "success" => true,
        "products" => $response,
        "packages" => $plans,
        "total" => count($plans)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
