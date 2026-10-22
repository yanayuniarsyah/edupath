<?php
// api/health.php
header('Content-Type: application/json');

if (isset($_GET['ping'])) {
    echo json_encode(["status" => "ok", "service" => "alive"]);
    exit;
}

require_once 'config.php';
try {
    $stmt = $pdo->query("SELECT 1");
    if ($stmt->fetch()) {
        echo json_encode(["status" => "ok", "db" => "connected"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "degraded", "db" => "unavailable"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "db" => "disconnected"]);
}
?>
