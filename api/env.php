<?php
// api/env.php
// Simple environment variable loader untuk PHP
// Membaca file .env di direktori yang sama dengan script ini
// Mendukung: key=value, #komentar, whitespace, quoted values

/**
 * Load environment variables dari file .env
 * 
 * @param string $path Path ke file .env (default: direktori file ini)
 * @return void
 */
function load_env(string $path = ''): void {
    if (empty($path)) {
        $path = __DIR__ . '/.env';
    }

    if (!file_exists($path) || !is_readable($path)) {
        // .env tidak wajib ada — server bisa set env via server config (cPanel, etc)
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip komentar
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Hapus kutip di awal/akhir jika ada
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Set ke $_ENV dan getenv() — hanya jika belum di-set oleh server environment
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

/**
 * Ambil nilai environment variable dengan fallback
 * 
 * @param string $key Nama variable
 * @param mixed  $default Nilai default jika tidak ditemukan
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false) {
        return $default;
    }

    // Type coercion untuk nilai boolean umum
    return match(strtolower((string)$value)) {
        'true', '1', 'yes'  => true,
        'false', '0', 'no'  => false,
        'null', ''          => $default,
        default             => $value,
    };
}
