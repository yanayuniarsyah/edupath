<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'register';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:5173';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require 'api/config.php';

$input = [
    'name' => 'Testing User',
    'email' => 'test80@test.com',
    'password' => '123'
];

require 'api/auth.php';
