<?php

/**
 * Vercel Serverless PHP Entrypoint for ChartSense AI (Laravel)
 */

// 1. Setup writable temporary storage paths for Vercel's read-only environment
$directories = [
    '/tmp/views',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app/public',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Setup SQLite database in /tmp if external cloud database is not configured
$targetDb = '/tmp/database.sqlite';
$sourceDb = __DIR__ . '/../database/database.sqlite';

if (!file_exists($targetDb) && file_exists($sourceDb)) {
    @copy($sourceDb, $targetDb);
} elseif (!file_exists($targetDb)) {
    @touch($targetDb);
}

// 3. Set serverless environment variables
putenv('VIEW_COMPILED_PATH=/tmp/views');
putenv('APP_CONFIG_CACHE=/tmp/config.php');
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
putenv('APP_EVENTS_CACHE=/tmp/events.php');
putenv('APP_SERVICES_CACHE=/tmp/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/packages.php');

if (empty(getenv('APP_KEY'))) {
    putenv('APP_KEY=base64:VKX4s38xGgAgQNonmNLE1TrOMbYKkKQr20BBz45cgtE=');
}

// If no external database host is provided, default to persistent /tmp SQLite
if (empty(getenv('DB_HOST')) || getenv('DB_HOST') === '127.0.0.1' || getenv('DB_HOST') === 'localhost') {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=/tmp/database.sqlite');
}

// 4. Forward execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
