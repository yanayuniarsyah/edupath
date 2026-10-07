<?php
$headers = [
    'Content-Type: application/json',
    'Accept: application/json, text/plain, */*',
    'Origin: https://edupath.co.id',
    'Referer: https://edupath.co.id/',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest: empty',
    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
];

$ch = curl_init('https://edupath.co.id/api/admin.php?action=login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP CODE: $code\nRESPONSE:\n$res\n";
