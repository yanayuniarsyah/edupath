<?php
require 'api/config.php';
try {
    $stmt = $pdo->query("SELECT tenant_id FROM students LIMIT 1");
    echo "tenant_id exists!";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
