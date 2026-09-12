<?php
// api/progress.php
require_once 'config.php';
require_once 'jwt.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

$payload = authenticate();
if ($payload->role !== 'student') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM progress WHERE student_id = ?");
    $stmt->execute([$payload->id]);
    $progress = $stmt->fetchAll();

    echo json_encode($progress);
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint progress tidak ditemukan"]);
}
?>
