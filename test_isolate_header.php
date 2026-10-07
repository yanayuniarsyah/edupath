<?php
$testHeaders = [
    'Origin' => 'Origin: https://edupath.co.id',
    'Referer' => 'Referer: https://edupath.co.id/',
    'User-Agent' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'Sec-Fetch-Site' => 'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode' => 'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest' => 'Sec-Fetch-Dest: empty',
    'Accept' => 'Accept: application/json, text/plain, */*'
];

foreach ($testHeaders as $name => $h) {
    $ch = curl_init('https://edupath.co.id/api/admin.php?action=login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'superadmin@uat.edupath.local', 'password' => 'EduPathSuperAdmin01!2026']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $h]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Header [$name]: HTTP $code\n";
}
