<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    // 1. Fetch active products without any runtime DDL
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

    // 2. Fetch active plans
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
            'name' => 'Paket Pejuang PTN',
            'description' => 'Akses penuh simulasi UTBK SNBT, bank soal 150 butir, dan rasionalisasi prodi.',
            'plans' => $plans
        ];
        $response = [$defaultGroup];
    }

    echo json_encode(["products" => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
