<?php
require 'api/config.php';

try {
    $cols = ['bank_name' => 'VARCHAR(50)', 'bank_account' => 'VARCHAR(50)', 'bank_owner' => 'VARCHAR(100)'];

    foreach ($cols as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE affiliates ADD COLUMN $col $type NULL");
            echo "✅ Added $col <br>";
        } catch (Exception $e) {
            echo "⚠️ Error $col: " . $e->getMessage() . "<br>";
        }
    }
    echo "Selesai menambahkan kolom rekening ke tabel affiliates.";
} catch (Exception $e) {
    echo "Koneksi Gagal: " . $e->getMessage();
}
