<?php
require 'api/config.php';
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE identity_key LIKE '%@uat.edupath.local'");
echo "UAT Accounts Persistence Verification: " . $stmt->fetchColumn() . " accounts remain intact in edupath_test.\n";
