<?php
require 'api/config.php';
echo 'orders NULL tenant: ' . $pdo->query("SELECT COUNT(*) FROM orders WHERE tenant_id IS NULL")->fetchColumn() . PHP_EOL;
echo 'orders total: ' . $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() . PHP_EOL;
echo 'plans NULL tenant: ' . $pdo->query("SELECT COUNT(*) FROM plans WHERE tenant_id IS NULL")->fetchColumn() . PHP_EOL;
echo 'plans total: ' . $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn() . PHP_EOL;
