<?php
/**
 * Migration: Phase 3i - Entitlement Student Ownership
 * 
 * Tujuan:
 * 1. Mentransfer kepemilikan langganan dan akses dari tenant ke student.
 * 2. Menghapus konstrain kepemilikan ganda (tenant XOR student).
 * 3. Mengunci (NOT NULL) kolom student_id untuk keamanan B2C.
 */

// Asumsi letak file: api/database/migrations/phase3i_entitlement_student_ownership.php
require_once dirname(__DIR__, 2) . '/config.php';

try {
    $pdo->beginTransaction();
    echo "Memulai migrasi Phase 3i...\n";

    // 1. Rekonstruksi subscriptions.student_id berdasarkan orders
    $affected_subs = $pdo->exec("
        UPDATE subscriptions s
        JOIN orders o ON s.payment_reference = o.id
        SET s.student_id = o.student_id
        WHERE s.student_id IS NULL
    ");
    echo "- Subscriptions diperbarui: $affected_subs\n";

    // 2. Rekonstruksi entitlements.student_id berdasarkan subscriptions
    $affected_ents = $pdo->exec("
        UPDATE entitlements e
        JOIN subscriptions s ON e.subscription_id = s.id
        SET e.student_id = s.student_id
        WHERE e.student_id IS NULL
    ");
    echo "- Entitlements diperbarui: $affected_ents\n";

    // 3. Hapus yatim piatu (orphan records) yang berbahaya
    // Jika masih ada data dengan student_id = NULL setelah rekonstruksi,
    // data tersebut adalah anomali manual yang menyebabkan mass privilege escalation.
    $deleted_ents = $pdo->exec("DELETE FROM `entitlements` WHERE `student_id` IS NULL");
    echo "- Orphan entitlements dihapus: $deleted_ents\n";

    $deleted_subs = $pdo->exec("DELETE FROM `subscriptions` WHERE `student_id` IS NULL");
    echo "- Orphan subscriptions dihapus: $deleted_subs\n";

    // 4. Drop check constraints lama
    try {
        $pdo->exec("ALTER TABLE `entitlements` DROP CONSTRAINT `ent_ownership_chk`");
        echo "- Constraint ent_ownership_chk dihapus.\n";
    } catch (PDOException $e) {
        echo "- Peringatan: Gagal menghapus ent_ownership_chk (mungkin sudah tidak ada). Pesan: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("ALTER TABLE `subscriptions` DROP CONSTRAINT `sub_ownership_chk`");
        echo "- Constraint sub_ownership_chk dihapus.\n";
    } catch (PDOException $e) {
        echo "- Peringatan: Gagal menghapus sub_ownership_chk (mungkin sudah tidak ada). Pesan: " . $e->getMessage() . "\n";
    }

    // 5. Force NOT NULL constraint
    $pdo->exec("ALTER TABLE `subscriptions` MODIFY COLUMN `student_id` VARCHAR(36) NOT NULL");
    echo "- Kolom subscriptions.student_id diubah menjadi NOT NULL.\n";

    $pdo->exec("ALTER TABLE `entitlements` MODIFY COLUMN `student_id` VARCHAR(36) NOT NULL");
    echo "- Kolom entitlements.student_id diubah menjadi NOT NULL.\n";

    $pdo->commit();
    echo "Migrasi Phase 3i berhasil diselesaikan.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Migrasi GAGAL: " . $e->getMessage() . "\n";
    exit(1);
}
