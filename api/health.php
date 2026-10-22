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
        echo json_encode(["status" => "degraded", "db" => "unavailable"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "db" => "disconnected", "detail" => $e->getMessage()]);
}
?>
