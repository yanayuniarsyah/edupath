<?php
// api/health.php
require_once 'config.php';

header('Content-Type: application/json');

// Minimal ping to database
try {
    $stmt = $pdo->query("SELECT 1");
    if ($stmt->fetch()) {
        echo json_encode(["status" => "ok", "db" => "connected"]);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "degraded", "db" => "unavailable"]);
    }
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(["status" => "error", "db" => "disconnected"]);
}
?>
