<?php
// router.php — Development & Test Router for PHP Built-in Server (php -S)
// Mirrors production Apache / LiteSpeed .htaccess security and rewrite policies.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// 1. Strict Security Blocklist (Matches .htaccess Require all denied)
$blockedPattern = '/(^\.|\.env|\.sql$|\.bak$|\.key$|\.pem$|\.log$|\.ya?ml$|^composer\.(json|lock)$|^package(-lock)?\.json$|^tsconfig\.json$|^vite\.config\.mjs$|^install_db\.php$|^import_.*\.php$|^test_.*\.php$|^seed_.*\.php$|^run_.*\.php$|^audit_.*\.php$|^deploy.*|\.(bat|cmd|sh|docx?|pdf|md)$)/i';

$basename = basename($uri);
if (preg_match($blockedPattern, $basename) || preg_match('/(^|\/)\./', $uri)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "error" => "403 Forbidden: Akses ke file ini diblokir demi keamanan."]);
    exit;
}

// 2. Prevent direct access to scratch or backups or tests directory
if (preg_match('#^/(scratch|api/backups|api/tests)/#i', $uri)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "error" => "403 Forbidden"]);
    exit;
}

// 3. Emulate api/.htaccess rewrite rules:
// RewriteRule ^auth/(.*)$ auth.php?action=$1
// RewriteRule ^admin/(.*)$ admin.php?action=$1
// RewriteRule ^progress/(.*)$ progress.php?action=$1
// RewriteRule ^payment/(.*)$ payment.php?action=$1
// RewriteRule ^quiz/(.*)$ quiz.php?action=$1
if (preg_match('#^/api/(auth|admin|progress|payment|quiz)/(.*)$#', $uri, $m)) {
    $_GET['action'] = $m[2];
    $targetScript = __DIR__ . '/api/' . $m[1] . '.php';
    if (file_exists($targetScript)) {
        require $targetScript;
        exit;
    }
}

// 4. If request is a PHP file that exists, execute it
if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// 5. If static file exists in dist or root, serve it with proper MIME
$distFile = __DIR__ . '/dist' . $uri;
if (is_file($distFile)) {
    $ext = strtolower(pathinfo($distFile, PATHINFO_EXTENSION));
    $mimes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'json' => 'application/json'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($distFile);
    exit;
}

if (is_file($file)) {
    return false;
}

// 6. SPA fallback for frontend routes (mirrors root .htaccess RewriteRule ^(.*)$ index.html)
if (!str_starts_with($uri, '/api/')) {
    $indexHtml = __DIR__ . '/dist/index.html';
    if (!file_exists($indexHtml)) {
        $indexHtml = __DIR__ . '/index.html';
    }
    if (file_exists($indexHtml)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexHtml);
        exit;
    }
}

// 7. Not found
http_response_code(404);
echo json_encode(["success" => false, "error" => "Not Found"]);
exit;
