<?php
// api/flush_opcache.php
// Script emergency satu kali pakai untuk clear OPcache di server produksi
// HAPUS file ini setelah digunakan!

$key = $_GET['key'] ?? '';
$expected = 'edupath-flush-2026';

if ($key !== $expected) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$results = [];

// 1. Clear PHP OPcache
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'SUCCESS' : 'FAILED';
    $results['opcache_status'] = opcache_get_status(false);
} else {
    $results['opcache_reset'] = 'opcache extension not loaded';
}

// 2. Clear APC/APCu if exists
if (function_exists('apcu_clear_cache')) {
    $results['apcu_clear'] = apcu_clear_cache() ? 'SUCCESS' : 'FAILED';
} else {
    $results['apcu_clear'] = 'apcu not loaded';
}

// 3. Verify file modification timestamps for critical files
$files = [
    'admin.php' => __DIR__ . '/admin.php',
    'auth.php'  => __DIR__ . '/auth.php',
];
foreach ($files as $name => $path) {
    $results['file_mtime'][$name] = file_exists($path)
        ? date('Y-m-d H:i:s T', filemtime($path))
        : 'NOT FOUND';
}

// 4. Confirm commit hash marker in admin.php
$adminContent = file_get_contents(__DIR__ . '/admin.php');
$results['admin_has_partial_update_fix'] = str_contains($adminContent, 'hasNameInput') ? 'YES - fix present' : 'NO - OLD version!';
$results['auth_has_admin_block'] = str_contains(file_get_contents(__DIR__ . '/auth.php'), 'wajib masuk melalui portal admin') ? 'YES - fix present' : 'NO - OLD version!';

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
