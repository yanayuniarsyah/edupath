<?php
// backup_db.php - Script untuk membackup database dari browser
require_once "config.php";

$secret_key = "rahasia123";
if (!isset($_GET["key"]) || $_GET["key"] !== $secret_key) {
    die("Akses ditolak. Gunakan url: backup_db.php?key=rahasia123");
}

$host = env("DB_HOST", "localhost");
$user = env("DB_USER");
$pass = env("DB_PASSWORD");
$name = env("DB_NAME");

$backup_file = "backup_" . date("Y-m-d-H-i-s") . ".sql";

// Perintah mysqldump
$command = "mysqldump --opt -h $host -u $user -p\"$pass\" $name > $backup_file";

echo "<h2>Proses Backup Database</h2>";
echo "Mencoba mengeksekusi mysqldump...<br>";

system($command, $output);

if ($output === 0) {
    echo "<p style=\"color:green;\">Backup berhasil! File: <a href=\"$backup_file\">$backup_file</a></p>";
    echo "<p>Silakan download file tersebut lalu HAPUS dari server demi keamanan.</p>";
} else {
    echo "<p style=\"color:red;\">Backup gagal. Fungsi system() atau mysqldump dinonaktifkan di cPanel.</p>";
    echo "<p>Silakan backup manual lewat cPanel (phpMyAdmin).</p>";
}
?>
