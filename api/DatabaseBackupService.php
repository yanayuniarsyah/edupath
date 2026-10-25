<?php
// api/DatabaseBackupService.php
// Pure-PHP MySQL/MariaDB Database Backup & Restore Service for EduPath
// Works on any environment (cPanel, CLI, VPS) without requiring mysqldump CLI binary.

class DatabaseBackupService {
    private PDO $pdo;
    private string $backupDir;

    public function __construct(PDO $pdo, ?string $backupDir = null) {
        $this->pdo = $pdo;
        $this->backupDir = $backupDir ?? (__DIR__ . '/backups');
        $this->ensureBackupDir();
    }

    /**
     * Pastikan direktori backup ada dan terproteksi dari akses HTTP langsung
     */
    public function ensureBackupDir(): void {
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }

        // Buat .htaccess proteksi akses langsung
        $htaccessPath = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "Order Allow,Deny\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n");
        }

        // Buat index.html kosong
        $indexPath = $this->backupDir . '/index.html';
        if (!file_exists($indexPath)) {
            @file_put_contents($indexPath, "<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1></body></html>");
        }
    }

    /**
     * Dapatkan path direktori backup
     */
    public function getBackupDir(): string {
        return $this->backupDir;
    }

    /**
     * Generate Full SQL Dump
     * @param bool $saveToFile Apakah disimpan ke file di direktori backup
     * @param string|null $customFilename Nama file kustom opsional
     * @return array Info hasil backup beserta isi SQL jika tidak disimpan
     */
    public function createBackup(bool $saveToFile = true, ?string $customFilename = null): array {
        $startTime = microtime(true);
        $dbName = '';
        try {
            $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn() ?: 'edupath';
        } catch (\Throwable $e) {
            $dbName = 'edupath';
        }

        $now = date('Y-m-d H:i:s');
        $filename = $customFilename ?? ('edupath_backup_' . date('Y-m-d_His') . '.sql');
        $cleanFilename = basename($filename);
        if (!str_ends_with($cleanFilename, '.sql')) {
            $cleanFilename .= '.sql';
        }

        // 1. Dapatkan daftar tabel
        $tables = [];
        try {
            $stmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (\Throwable $e) {
            // Fallback SQLite / generic
            try {
                $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable $e2) {
                $tables = [];
            }
        }

        $totalRows = 0;
        $tableCount = count($tables);

        // Header SQL
        $out = "-- =====================================================================\n";
        $out .= "-- EDUPATH SAAS PLATFORM — FULL DATABASE BACKUP DUMP\n";
        $out .= "-- Database: " . $dbName . "\n";
        $out .= "-- Generated At: " . $now . " (Asia/Jakarta)\n";
        $out .= "-- Tables Count: " . $tableCount . "\n";
        $out .= "-- Generator: EduPath Pure-PHP Database Backup Engine\n";
        $out .= "-- =====================================================================\n\n";

        $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $out .= "SET time_zone = \"+07:00\";\n\n";

        foreach ($tables as $table) {
            // Skip sqlite internal if any
            if ($table === 'sqlite_sequence') continue;

            $out .= "-- -------------------------------------------------------------\n";
            $out .= "-- Table structure for `$table`\n";
            $out .= "-- -------------------------------------------------------------\n";
            $out .= "DROP TABLE IF EXISTS `$table`;\n";

            // CREATE TABLE
            try {
                $createStmt = $this->pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $createSql = $createStmt['Create Table'] ?? ($createStmt['create table'] ?? '');
                if ($createSql) {
                    $out .= $createSql . ";\n\n";
                }
            } catch (\Throwable $e) {
                // SQLite fallback
                try {
                    $cStmt = $this->pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
                    $cStmt->execute([$table]);
                    $sql = $cStmt->fetchColumn();
                    if ($sql) $out .= $sql . ";\n\n";
                } catch (\Throwable $e2) {}
            }

            // DUMP ROWS (Batching 100 rows per chunk)
            $out .= "-- Dumping data for `$table`\n";
            $rowCount = 0;

            try {
                $countStmt = $this->pdo->query("SELECT COUNT(*) FROM `$table`");
                $rowCount = (int)$countStmt->fetchColumn();
            } catch (\Throwable $e) {
                $rowCount = 0;
            }

            $totalRows += $rowCount;

            if ($rowCount > 0) {
                $offset = 0;
                $batchSize = 250;

                while ($offset < $rowCount) {
                    $dataStmt = $this->pdo->query("SELECT * FROM `$table` LIMIT $batchSize OFFSET $offset");
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) break;

                    $columns = array_keys($rows[0]);
                    $escapedCols = array_map(function($c) { return "`$c`"; }, $columns);
                    $colList = implode(', ', $escapedCols);

                    $valueLines = [];
                    foreach ($rows as $row) {
                        $escapedValues = [];
                        foreach ($columns as $col) {
                            $val = $row[$col];
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $escapedValues[] = $val;
                            } else {
                                $escapedValues[] = $this->pdo->quote((string)$val);
                            }
                        }
                        $valueLines[] = "(" . implode(', ', $escapedValues) . ")";
                    }

                    $out .= "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $valueLines) . ";\n";
                    $offset += $batchSize;
                }
                $out .= "\n";
            }
        }

        $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $out .= "-- Dump completed at " . date('Y-m-d H:i:s') . "\n";

        $filepath = null;
        $sizeBytes = strlen($out);

        if ($saveToFile) {
            $filepath = $this->backupDir . '/' . $cleanFilename;
            file_put_contents($filepath, $out);
            $sizeBytes = filesize($filepath);
        }

        $duration = round(microtime(true) - $startTime, 3);

        return [
            "success"        => true,
            "filename"       => $cleanFilename,
            "filepath"       => $filepath,
            "size_bytes"     => $sizeBytes,
            "size_formatted" => $this->formatBytes($sizeBytes),
            "table_count"    => $tableCount,
            "row_count"      => $totalRows,
            "duration_sec"   => $duration,
            "created_at"     => $now,
            "sql_content"    => $saveToFile ? null : $out
        ];
    }

    /**
     * Daftar seluruh snapshot file backup yang ada di server
     */
    public function listBackups(): array {
        $files = @scandir($this->backupDir) ?: [];
        $backups = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with(strtolower($file), '.sql')) {
                continue;
            }

            $filepath = $this->backupDir . '/' . $file;
            if (!is_file($filepath)) continue;

            $size = filesize($filepath);
            $mtime = filemtime($filepath);

            $backups[] = [
                "filename"       => $file,
                "size_bytes"     => $size,
                "size_formatted" => $this->formatBytes($size),
                "created_at"     => date('Y-m-d H:i:s', $mtime),
                "is_safety"      => str_contains($file, 'pre_restore_safety')
            ];
        }

        // Urutkan dari yang paling baru
        usort($backups, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * Dapatkan path file backup dengan validasi path traversal
     */
    public function getBackupFile(string $filename): string {
        $clean = basename($filename);
        $path = $this->backupDir . '/' . $clean;

        if (!file_exists($path) || !is_file($path) || !str_ends_with(strtolower($clean), '.sql')) {
            throw new InvalidArgumentException("File backup tidak ditemukan atau tidak valid.");
        }

        return $path;
    }

    /**
     * Hapus snapshot file backup
     */
    public function deleteBackup(string $filename): bool {
        $path = $this->getBackupFile($filename);
        return @unlink($path);
    }

    /**
     * Eksekusi Restore Database dari string SQL
     * @param string $sqlContent Konten perintah SQL
     * @param string|null $userId ID Superadmin penanggung jawab
     * @param bool $createSafetyBackup Buat backup darurat sebelum restore
     */
    public function restoreFromSql(string $sqlContent, ?string $userId = null, bool $createSafetyBackup = true): array {
        $startTime = microtime(true);

        if (empty(trim($sqlContent))) {
            throw new InvalidArgumentException("Konten SQL kosong.");
        }

        // 1. Buat Pre-Restore Safety Backup
        $safetyBackup = null;
        if ($createSafetyBackup) {
            try {
                $safetyBackup = $this->createBackup(true, 'pre_restore_safety_' . date('Y-m-d_His') . '.sql');
            } catch (\Throwable $e) {
                // Jangan batalkan jika gagal simpan safety, tapi catat
            }
        }

        // 2. Parse SQL statements cerdas
        $driver = '';
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {}
        $isSqlite = ($driver === 'sqlite');

        $statements = $this->splitSqlStatements($sqlContent, $isSqlite);
        if (empty($statements)) {
            throw new InvalidArgumentException("Tidak ada statement SQL valid yang ditemukan dalam file.");
        }

        $executedCount = 0;
        $failedCount = 0;
        $errors = [];

        // Disable Foreign Keys
        try { 
            if ($isSqlite) {
                $this->pdo->exec("PRAGMA foreign_keys = OFF");
            } else {
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            }
        } catch (\Throwable $e) {}

        foreach ($statements as $stmtSql) {
            $stmtSql = trim($stmtSql);
            if (empty($stmtSql)) continue;

            try {
                $this->pdo->exec($stmtSql);
                $executedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                if (count($errors) < 10) {
                    $errors[] = $e->getMessage() . " [SQL: " . substr($stmtSql, 0, 100) . "...]";
                }
            }
        }

        // Re-enable Foreign Keys
        try { 
            if ($isSqlite) {
                $this->pdo->exec("PRAGMA foreign_keys = ON");
            } else {
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        } catch (\Throwable $e) {}

        $duration = round(microtime(true) - $startTime, 3);

        // Catat jejak audit
        try {
            $evId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $this->pdo->prepare("
                INSERT INTO audit_logs (id, tenant_id, user_id, action, entity_type, details)
                VALUES (?, 'system', ?, 'database_restore', 'DATABASE', ?)
            ")->execute([
                $evId,
                $userId,
                json_encode([
                    "statements_total" => count($statements),
                    "statements_executed" => $executedCount,
                    "statements_failed" => $failedCount,
                    "safety_backup" => $safetyBackup['filename'] ?? null,
                    "duration_sec" => $duration
                ])
            ]);
        } catch (\Throwable $e) {}

        return [
            "success"             => $failedCount === 0 || ($executedCount > 0 && $failedCount < ($executedCount * 0.1)),
            "statements_total"    => count($statements),
            "statements_executed" => $executedCount,
            "statements_failed"   => $failedCount,
            "errors"              => $errors,
            "safety_backup"       => $safetyBackup['filename'] ?? null,
            "duration_sec"        => $duration
        ];
    }

    /**
     * Restore database dari file snapshot yang tersimpan di server
     */
    public function restoreFromFile(string $filename, ?string $userId = null): array {
        $path = $this->getBackupFile($filename);
        $sqlContent = file_get_contents($path);
        if ($sqlContent === false) {
            throw new RuntimeException("Gagal membaca file snapshot backup: $filename");
        }

        return $this->restoreFromSql($sqlContent, $userId, true);
    }

    /**
     * Parser SQL Statement yang memisahkan delimiter semicolon (;)
     * tanpa merusak string yang mengandung semicolon di dalam quotes
     * dan membersihkan komentar SQL (-- / # / /* ... * /) secara akurat
     */
    public function splitSqlStatements(string $sql, bool $isSqlite = false): array {
        $statements = [];
        $current = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';
            $prev = ($i > 0) ? $sql[$i - 1] : '';

            // Handle Quotes (hanya jika tidak di dalam quote tipe lain)
            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                if ($prev !== '\\') {
                    $inSingleQuote = !$inSingleQuote;
                }
            } elseif ($char === '"' && !$inSingleQuote && !$inBacktick) {
                if ($prev !== '\\') {
                    $inDoubleQuote = !$inDoubleQuote;
                }
            } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
            }

            // Jika di luar string literal, deteksi komentar dan delimiter
            if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                // Komentar baris tunggal: -- atau #
                if (($char === '-' && $next === '-') || $char === '#') {
                    while ($i < $len && $sql[$i] !== "\n") {
                        $i++;
                    }
                    continue;
                }

                // Komentar blok multi-baris: /* ... */
                if ($char === '/' && $next === '*') {
                    $i += 2;
                    while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                        $i++;
                    }
                    $i++; // lewati slash penutup
                    continue;
                }

                // Semicolon separator
                if ($char === ';') {
                    $trimmed = trim($current);
                    if (!empty($trimmed)) {
                        if ($isSqlite && str_starts_with(strtoupper($trimmed), 'SET ')) {
                            // Abaikan variabel session MySQL jika dijalankan di SQLite
                        } else {
                            $statements[] = $trimmed;
                        }
                    }
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if (!empty($trimmed)) {
            if (!($isSqlite && str_starts_with(strtoupper($trimmed), 'SET '))) {
                $statements[] = $trimmed;
            }
        }

        return $statements;
    }

    /**
     * Helper format byte ke KB / MB / GB
     */
    private function formatBytes(int $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' Bytes';
    }
}
