<?php

// Menyiapkan database SQLite di folder /tmp saat berjalan di serverless Vercel
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    $tmpDb = '/tmp/database.sqlite';
    if (!file_exists($tmpDb)) {
        $sourceDb = __DIR__ . '/../database/database.sqlite';
        if (file_exists($sourceDb)) {
            copy($sourceDb, $tmpDb);
        } else {
            touch($tmpDb);
        }
    }
    putenv("DB_CONNECTION=sqlite");
    putenv("DB_DATABASE={$tmpDb}");
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = $tmpDb;
    $_SERVER['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_DATABASE'] = $tmpDb;
}

// Forward Vercel requests to normal index.php
require __DIR__ . '/../public/index.php';
