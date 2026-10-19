<?php
require_once 'config.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

function ensure_default_products_and_plans($pdo) {
    try {
        // 1. Ensure products table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id VARCHAR(50) PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(150) NOT NULL,
                description TEXT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Ensure columns exist in plans table
        try {
            $pdo->exec("ALTER TABLE plans ADD COLUMN billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly'");
        } catch (Exception $e) {}

        try {
            $pdo->exec("ALTER TABLE plans ADD COLUMN product_id VARCHAR(50) NULL");
        } catch (Exception $e) {}

        // 3. Insert or update default 3 products
        $defaultProducts = [
            [
                'id' => 'prod-p1',
                'code' => 'STARTER',
                'name' => 'Paket Mandiri',
                'description' => 'Mulai belajar adaptif dengan IRT assessment & 50.000+ soal bank soal.'
            ],
            [
                'id' => 'prod-p2',
                'code' => 'UTBK',
                'name' => 'Paket Pejuang PTN',
                'description' => 'AI Tutor 24/7, Adaptive IRT tanpa batas, & Rasionalisasi Prodi real-time.'
            ],
            [
                'id' => 'prod-p3',
                'code' => 'ELITE',
                'name' => 'Paket Elite Mentoring',
                'description' => '1-on-1 Zoom Mentoring dengan Senior Mentor alumni UI/ITB + akses semua fitur.'
            ]
        ];

        foreach ($defaultProducts as $p) {
            $stmt = $pdo->prepare("
                INSERT INTO products (id, code, name, description, status)
                VALUES (?, ?, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    description = VALUES(description),
                    status = 'active'
            ");
            $stmt->execute([$p['id'], $p['code'], $p['name'], $p['description']]);
        }

        // 4. Default 6 plans (3 products x 2 billing cycles)
        $p1Features = json_encode([
            '500+ Micro-lessons & Video Ringkas',
            '50.000+ Soal Bank Soal IRT',
            '5x Tryout Nasional Simulasi SNBT',
            'Diagnostic Assessment Awal',
            'Dashboard Progres Belajar'
        ]);

        $p2Features = json_encode([
            'Semua fitur Paket Mandiri',
            'AI Tutor Companion 24/7 Tanpa Batas',
            'Adaptive IRT Unlimited + Remedial Otomatis',
            'Rasionalisasi Prodi & Prediksi Kelulusan',
            'Laporan Progres ke Orang Tua via Telegram',
            'Grup Belajar VIP Komunitas PTN'
        ]);

        $p3Features = json_encode([
            'Semua fitur Paket Pejuang PTN',
            '1-on-1 Zoom Mentoring Private (4x/bulan)',
            'SOS Live Tutor Senior Mentor UI/ITB',
            'Analisis Soal UTBK Spesifik per Individu',
            'Garansi Score Improvement atau Refund',
            'Akses Eksklusif Beta Fitur Terbaru'
        ]);

        $defaultPlans = [
            // Paket Mandiri: 180.000 / bln, Diskon tahunan 40% (108.000/bln -> 1.296.000/thn)
            ['plan-p1-monthly', 'prod-p1', 'Paket Mandiri (Bulanan)', 180000, 0, 30, $p1Features, 'monthly'],
            ['plan-p1-yearly',  'prod-p1', 'Paket Mandiri (Tahunan)', 1296000, 40, 365, $p1Features, 'yearly'],

            // Paket Pejuang PTN: 480.000 / bln, Diskon tahunan 60% (192.000/bln -> 2.304.000/thn)
            ['plan-p2-monthly', 'prod-p2', 'Paket Pejuang PTN (Bulanan)', 480000, 0, 30, $p2Features, 'monthly'],
            ['plan-p2-yearly',  'prod-p2', 'Paket Pejuang PTN (Tahunan)', 2304000, 60, 365, $p2Features, 'yearly'],

            // Paket Elite Mentoring: 980.000 / bln, Diskon tahunan 30% (686.000/bln -> 8.232.000/thn)
            ['plan-p3-monthly', 'prod-p3', 'Paket Elite Mentoring (Bulanan)', 980000, 0, 30, $p3Features, 'monthly'],
            ['plan-p3-yearly',  'prod-p3', 'Paket Elite Mentoring (Tahunan)', 8232000, 30, 365, $p3Features, 'yearly'],
        ];

        foreach ($defaultPlans as $pl) {
            $stmt = $pdo->prepare("
                INSERT INTO plans (id, product_id, name, price, discount, duration, features, billing_cycle, is_active, is_archived)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
                ON DUPLICATE KEY UPDATE
                    product_id = VALUES(product_id),
                    name = VALUES(name),
                    price = VALUES(price),
                    discount = VALUES(discount),
                    duration = VALUES(duration),
                    features = VALUES(features),
                    billing_cycle = VALUES(billing_cycle),
                    is_active = 1,
                    is_archived = 0
            ");
            $stmt->execute($pl);
        }

        // Archive unlinked legacy plan IDs
        $pdo->exec("UPDATE plans SET is_archived = 1 WHERE id IN ('plan-mandiri', 'plan-utama', 'plan-vip')");
    } catch (Exception $e) {
        error_log("Error in ensure_default_products_and_plans: " . $e->getMessage());
    }
}

try {
    // Check if products exist; if fewer than 3, auto-seed defaults
    $countProducts = 0;
    try {
        $countProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    } catch (Exception $e) {
        $countProducts = 0;
    }

    if ($countProducts < 3) {
        ensure_default_products_and_plans($pdo);
    }

    // We fetch products first, then their plans
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

    // If still less than 3 products matched with plans, ensure and reload once
    if (count($response) < 3) {
        ensure_default_products_and_plans($pdo);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt_plans->execute();
        $plans = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);
        $plans_by_product = [];
        foreach ($plans as $plan) {
            $pid = $plan['product_id'];
            if ($pid) $plans_by_product[$pid][] = $plan;
        }
        $response = [];
        foreach ($products as $prod) {
            if (isset($plans_by_product[$prod['id']])) {
                $prod['plans'] = $plans_by_product[$prod['id']];
                $response[] = $prod;
            }
        }
    }

    echo json_encode(["products" => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
