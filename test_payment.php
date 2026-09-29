<?php
$_GET['action'] = 'create';
$_SERVER['REQUEST_METHOD'] = 'POST';
$inputData = json_encode(['plan_id' => 'Premium_1', 'amount' => 149000]);
file_put_contents('php://input', $inputData);

// mock authenticate() by including it before or defining it?
// Actually payment.php requires 'jwt.php' which defines authenticate().
// We need to bypass authenticate or provide a valid JWT.
// Let's just catch the fatal error!
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        echo "FATAL ERROR CAUGHT: " . json_encode($error);
    }
});

require 'api/payment.php';
