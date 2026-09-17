<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'EduPath';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cols = ['bank_name' => 'VARCHAR(50)', 'bank_account' => 'VARCHAR(50)', 'bank_owner' => 'VARCHAR(100)'];

    foreach ($cols as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE affiliates ADD COLUMN $col $type NULL");
            echo "✅ Added $col\n";
        } catch (Exception $e) {
            echo "⚠️ Error $col: " . $e->getMessage() . "\n";
        }
    }
    echo "\nSelesai menambahkan kolom rekening ke tabel affiliates.\n";
} catch (Exception $e) {
    echo "Koneksi Gagal: " . $e->getMessage();
}
