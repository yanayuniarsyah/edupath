<?php
// api/track_visitor.php
// Endpoint ringan untuk mencatat trafik pengunjung, alamat IP, dan asal kota
// Digunakan oleh frontend EduPath untuk statistik superadmin

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rate_limit.php';

header('Content-Type: application/json');

// Tangkap IP client menggunakan get_client_ip()
$ip = get_client_ip();
if (empty($ip) || $ip === '0.0.0.0') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: [];
$pageUrl = substr(trim($input['page'] ?? ($_SERVER['HTTP_REFERER'] ?? '/')), 0, 500);
$userAgent = substr(trim($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);

// Deteksi kota, wilayah, dan negara
$city = 'Tidak Diketahui';
$region = 'Indonesia';
$country = 'Indonesia';

// 1. Cek Cloudflare Geolocation Headers jika tersedia
if (!empty($_SERVER['HTTP_CF_IPCITY'])) {
    $city = trim($_SERVER['HTTP_CF_IPCITY']);
}
if (!empty($_SERVER['HTTP_CF_REGION'])) {
    $region = trim($_SERVER['HTTP_CF_REGION']);
}
if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
    $country = trim($_SERVER['HTTP_CF_IPCOUNTRY']);
    if ($country === 'ID') $country = 'Indonesia';
}

// 2. Deteksi Alamat IP Lokal / Private
$isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost']) || 
           filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

if ($isLocal) {
    if ($city === 'Tidak Diketahui') {
        $city = 'Lokal (Dev/Host)';
        $region = 'DKI Jakarta';
        $country = 'Indonesia';
    }
} else {
    // 3. Jika bukan lokal dan kota belum diketahui, cek apakah IP sudah ada di database
    try {
        $existing = $pdo->prepare("SELECT city, region, country FROM visitors WHERE ip_address = ? LIMIT 1");
        $existing->execute([$ip]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['city']) && $row['city'] !== 'Tidak Diketahui') {
            $city = $row['city'];
            $region = $row['region'] ?: $region;
            $country = $row['country'] ?: $country;
        } else {
            // Coba resolusi cepat via ip-api (timeout 2 detik, non-blocking)
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            $geoJson = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,regionName,country", false, $ctx);
            if ($geoJson) {
                $geo = json_decode($geoJson, true);
                if (isset($geo['status']) && $geo['status'] === 'success') {
                    if (!empty($geo['city'])) $city = $geo['city'];
                    if (!empty($geo['regionName'])) $region = $geo['regionName'];
                    if (!empty($geo['country'])) $country = $geo['country'];
                }
            }
        }
    } catch (\Throwable $e) {}
}

// Simpan atau perbarui log kunjungan
try {
    // Pastikan tabel visitors ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visitors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            city VARCHAR(100) DEFAULT 'Tidak Diketahui',
            region VARCHAR(100) NULL,
            country VARCHAR(100) DEFAULT 'Indonesia',
            user_agent VARCHAR(500) NULL,
            page_url VARCHAR(500) NULL,
            visit_count INT DEFAULT 1,
            first_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ip (ip_address),
            INDEX idx_last_visit (last_visit),
            INDEX idx_city (city)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->prepare("
        INSERT INTO visitors (ip_address, city, region, country, user_agent, page_url, visit_count, last_visit)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
            visit_count = visit_count + 1,
            last_visit = NOW(),
            page_url = VALUES(page_url),
            user_agent = VALUES(user_agent),
            city = IF(city = 'Tidak Diketahui' AND VALUES(city) != 'Tidak Diketahui', VALUES(city), city),
            region = IF(region IS NULL AND VALUES(region) IS NOT NULL, VALUES(region), region)
    ");
    $stmt->execute([$ip, $city, $region, $country, $userAgent, $pageUrl]);

    echo json_encode([
        "success" => true,
        "ip" => $ip,
        "city" => $city,
        "region" => $region,
        "country" => $country
    ]);
} catch (\Throwable $e) {
    http_response_code(200); // Jangan gagalkan halaman utama
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
