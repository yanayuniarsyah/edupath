<?php
// api/cache_bust.php
// Force OPcache invalidation per-file
// HAPUS setelah digunakan!

$key = $_GET['key'] ?? '';
if ($key !== 'edupath-bust-2026') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$apiDir = __DIR__;
$results = [];
$phpFiles = glob($apiDir . '/*.php');

foreach ($phpFiles as $file) {
    $basename = basename($file);
    if ($basename === 'cache_bust.php') continue;

    // 1. Touch file to update mtime → forces OPcache miss
    $touched = touch($file);

    // 2. Explicitly invalidate in OPcache per-file
    $invalidated = false;
    if (function_exists('opcache_invalidate')) {
        $invalidated = opcache_invalidate($file, true); // force=true
    }

    $results[$basename] = [
        'touched'     => $touched,
        'invalidated' => $invalidated,
        'new_mtime'   => date('Y-m-d H:i:s T', filemtime($file)),
    ];
}

// Reset OPcache setelah per-file invalidation
$reset = function_exists('opcache_reset') ? opcache_reset() : false;

header('Content-Type: application/json');
echo json_encode([
    'status'      => 'done',
    'opcache_reset_after' => $reset,
    'files'       => $results,
    'admin_fix_check' => str_contains(file_get_contents($apiDir . '/admin.php'), 'hasNameInput') ? 'OK - partial update fix ada' : 'MISSING - fix BELUM ada!',
    'auth_fix_check'  => str_contains(file_get_contents($apiDir . '/auth.php'), 'wajib masuk melalui portal admin') ? 'OK - login block fix ada' : 'MISSING - fix BELUM ada!',
], JSON_PRETTY_PRINT);
