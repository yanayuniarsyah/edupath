<?php
require_once 'config.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    // We only expose active products and active plans
    // We fetch products first, then their plans
    $stmt = $pdo->prepare("
        SELECT id, code, name, description 
        FROM products 
        WHERE status = 'active'
        ORDER BY name ASC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch plans
    $stmt_plans = $pdo->prepare("
        SELECT id, product_id, name, price, discount, duration, features, billing_cycle
        FROM plans
        WHERE is_active = 1 AND is_archived = 0
        ORDER BY price ASC
    ");
    $stmt_plans->execute();
    $plans = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);

    // Group plans by product_id
    $plans_by_product = [];
    foreach ($plans as $plan) {
        $pid = $plan['product_id'];
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

    // Include orphaned plans (plans without product_id) for backward compatibility
    $orphaned = [];
    foreach ($plans as $plan) {
        if (!$plan['product_id']) {
            $orphaned[] = $plan;
        }
    }
    
    if (count($orphaned) > 0) {
        $response[] = [
            'id' => 'legacy',
            'code' => 'LEGACY',
            'name' => 'Paket Lainnya',
            'description' => 'Paket lama yang belum dikelompokkan',
            'plans' => $orphaned
        ];
    }

    echo json_encode(["products" => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
