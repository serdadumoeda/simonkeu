<?php

// Menyiapkan direktori penyimpanan dan database di lingkungan serverless Vercel
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    // 1. Buat struktur folder storage di /tmp karena sistem berkas Vercel bersifat Read-Only
    $storageDirs = [
        '/tmp/storage/app',
        '/tmp/storage/framework/cache',
        '/tmp/storage/framework/sessions',
        '/tmp/storage/framework/views',
        '/tmp/storage/logs',
        '/tmp/bootstrap/cache',
    ];

    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    // Set jalur cache & tempat kompilasi view ke /tmp
    putenv("VIEW_COMPILED_PATH=/tmp/storage/framework/views");
    $_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
    $_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

    putenv("APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php");
    $_ENV['APP_SERVICES_CACHE'] = '/tmp/bootstrap/cache/services.php';

    putenv("APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php");
    $_ENV['APP_PACKAGES_CACHE'] = '/tmp/bootstrap/cache/packages.php';

    putenv("APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php");
    $_ENV['APP_CONFIG_CACHE'] = '/tmp/bootstrap/cache/config.php';

    putenv("APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes.php");
    $_ENV['APP_ROUTES_CACHE'] = '/tmp/bootstrap/cache/routes.php';

    // 2. Cek apakah ada konfigurasi PostgreSQL / DB Eksternal dari Vercel Environment
    $postgresUrl = getenv('POSTGRES_URL') ?: ($_ENV['POSTGRES_URL'] ?? ($_SERVER['POSTGRES_URL'] ?? null));
    $postgresHost = getenv('POSTGRES_HOST') ?: ($_ENV['POSTGRES_HOST'] ?? ($_SERVER['POSTGRES_HOST'] ?? null));
    $dbConn = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? ($_SERVER['DB_CONNECTION'] ?? null));

    $hasExternalDb = !empty($postgresUrl) || !empty($postgresHost) || ($dbConn && $dbConn !== 'sqlite');

    if (!$hasExternalDb) {
        // Fallback ke SQLite jika tidak ada PostgreSQL / DB Eksternal yang diset
        $tmpDb = '/tmp/database.sqlite';
        $sourceDb = __DIR__ . '/../database/database.sqlite';

        if (!file_exists($tmpDb) || filesize($tmpDb) === 0) {
            if (file_exists($sourceDb) && filesize($sourceDb) > 0) {
                @copy($sourceDb, $tmpDb);
            } else {
                @touch($tmpDb);
            }
        }

        putenv("DB_CONNECTION=sqlite");
        putenv("DB_DATABASE={$tmpDb}");
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $tmpDb;
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $tmpDb;

        // Auto-migrate & seed SQLite jika tabel users belum ada di /tmp/database.sqlite
        try {
            if (file_exists($tmpDb)) {
                $pdo = new PDO('sqlite:' . $tmpDb);
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                $hasUsersTable = $stmt && $stmt->fetch();

                if (!$hasUsersTable) {
                    require_once __DIR__ . '/../vendor/autoload.php';
                    $app = require_once __DIR__ . '/../bootstrap/app.php';
                    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
                    $kernel->call('migrate', ['--force' => true]);
                    $kernel->call('db:seed', ['--force' => true]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan error auto-migrate jika sudah di-handle
        }
    }
}

// Forward Vercel requests to normal index.php
require __DIR__ . '/../public/index.php';
