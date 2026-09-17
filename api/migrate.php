<?php
// migrate.php - Script untuk menjalankan migrasi dari browser
require_once 'config.php';

$secret_key = 'rahasia123';
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Akses ditolak. Gunakan url: migrate.php?key=rahasia123");
}

echo "<h2>Proses Migrasi Database</h2>";

try {
    // Masukkan script migrasi Anda di sini (misal: penambahan kolom atau tabel baru)
    // Contoh untuk mengeksekusi script yang sudah ada:
    
    echo "Menjalankan update_questions_schema.php...<br>";
    include '../update_questions_schema.php';
    echo "<br>";
    
    // Tambahkan migrasi lain di sini jika diperlukan
    // include 'file_migrasi_lainnya.php';

    echo "<p style='color:green;'>Migrasi selesai (jika tidak ada error di atas).</p>";
    echo "<p>Harap <b>HAPUS</b> file ini (migrate.php) dari server demi keamanan.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Terjadi kesalahan: " . $e->getMessage() . "</p>";
}
?>
