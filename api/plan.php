<?php
// api/plan.php
require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json');

$payload = authenticate($pdo);
if (!$payload || !in_array($payload->role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Only authorized administrators can manage plans.']);
    exit;
}

$tenant_id = $payload->tenant_id;
if ($payload->role === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenant_id = $_GET['tenant_id'];
}

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Tenant ID is required']);
    exit;
}

$action = $_GET['action'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare('SELECT id, name, price, discount, duration, is_active, is_archived, features, created_at, updated_at FROM plans WHERE tenant_id = ? ORDER BY is_archived ASC, is_active DESC, created_at DESC');
        $stmt->execute([$tenant_id]);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plans as &$plan) {
            if ($plan['features']) { $plan['features'] = json_decode($plan['features'], true); }
        }
        echo json_encode(['success' => true, 'data' => $plans]);
        break;

    case 'detail':
        $id = $_GET['id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenant_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($plan) {
            if ($plan['features']) $plan['features'] = json_decode($plan['features'], true);
            echo json_encode(['success' => true, 'data' => $plan]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Plan not found']);
        }
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
        $name = trim($input['name'] ?? '');
        $price = $input['price'] ?? 0;
        $discount = $input['discount'] ?? 0;
        $duration = $input['duration'] ?? 30;
        $features = isset($input['features']) ? json_encode($input['features']) : null;
        if (empty($name) || !is_numeric($price)) { http_response_code(400); echo json_encode(['error' => 'Name and valid price are required']); exit; }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        $stmt = $pdo->prepare('INSERT INTO plans (id, tenant_id, name, price, discount, duration, features, is_active, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)');
        try {
            $stmt->execute([$id, $tenant_id, $name, $price, $discount, $duration, $features]);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) { http_response_code(500); echo json_encode(['error' => 'Failed to create plan: ' . $e->getMessage()]); }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $price = $input['price'] ?? null;
        $discount = $input['discount'] ?? null;
        $duration = $input['duration'] ?? null;
        $features = isset($input['features']) ? json_encode($input['features']) : null;
        if (empty($id) || empty($name) || $price === null) { http_response_code(400); exit; }
        $stmt = $pdo->prepare('UPDATE plans SET name = ?, price = ?, discount = ?, duration = ?, features = ? WHERE id = ? AND tenant_id = ?');
        try {
            $stmt->execute([$name, $price, $discount, $duration, $features, $id, $tenant_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) { http_response_code(500); }
        break;

    case 'activate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = $input['id'] ?? '';
        $pdo->prepare('UPDATE plans SET is_active = 1 WHERE id = ? AND tenant_id = ?')->execute([$id, $tenant_id]);
        echo json_encode(['success' => true]);
        break;

    case 'deactivate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = $input['id'] ?? '';
        $pdo->prepare('UPDATE plans SET is_active = 0 WHERE id = ? AND tenant_id = ?')->execute([$id, $tenant_id]);
        echo json_encode(['success' => true]);
        break;

    case 'archive':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = $input['id'] ?? '';
        $pdo->prepare('UPDATE plans SET is_archived = 1, is_active = 0 WHERE id = ? AND tenant_id = ?')->execute([$id, $tenant_id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400); echo json_encode(['error' => 'Invalid action']); break;
}

