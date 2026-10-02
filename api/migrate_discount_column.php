<?php
// api/migrate_discount_column.php
// Menambahkan kolom discount & billing_cycle ke tabel plans (jika belum ada)
// Aman dijalankan berkali-kali (idempotent). HAPUS setelah dijalankan di production.

require_once "config.php";
header("Content-Type: text/plain; charset=utf-8");

function col_exists($pdo, $table, $col) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$table, $col]);
    return (int)$s->fetchColumn() > 0;
}

// --- discount ---
if (col_exists($pdo, "plans", "discount")) {
    echo "OK: Kolom 'discount' sudah ada.\n";
} else {
    try {
        $pdo->exec("ALTER TABLE plans ADD COLUMN discount DECIMAL(15,2) DEFAULT 0.00 AFTER price");
        echo "SUCCESS: Kolom 'discount' ditambahkan.\n";
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        http_response_code(500); exit;
    }
}

// --- billing_cycle ---
if (col_exists($pdo, "plans", "billing_cycle")) {
    echo "OK: Kolom 'billing_cycle' sudah ada.\n";
} else {
    try {
        $pdo->exec("ALTER TABLE plans ADD COLUMN billing_cycle VARCHAR(20) DEFAULT 'monthly' AFTER duration");
        echo "SUCCESS: Kolom 'billing_cycle' ditambahkan.\n";
    } catch (PDOException $e) {
        echo "WARN: " . $e->getMessage() . "\n";
    }
}

echo "\nMigrasi selesai. Hapus file ini dari server.\n";
?>
