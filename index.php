<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Polyfill mbstring functions if extension disabled on server
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) {
        return preg_split('/' . $pattern . '/u', $string, $limit);
    }
}

// Redirect to installer if .env or installed.lock does not exist
if ((!file_exists(__DIR__.'/.env') || !file_exists(__DIR__.'/installed.lock')) && file_exists(__DIR__.'/install.php')) {
    header('Location: install.php');
    exit;
}

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer Autoloader
if (file_exists($autoload = __DIR__.'/vendor/autoload.php')) {
    require $autoload;
} else {
    die('Vendor autoloader missing. Please run "composer install" on your server.');
}

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// Set public path to current directory for cPanel root deployment
$app->usePublicPath(__DIR__);

// Handle request with graceful fallback to installer on database driver failure
try {
    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    if ((str_contains($e->getMessage(), 'could not find driver') || str_contains($e->getMessage(), 'sqlite') || str_contains($e->getMessage(), 'SQLSTATE') || str_contains($e->getMessage(), 'Access denied') || str_contains($e->getMessage(), 'Connection refused')) && file_exists(__DIR__.'/install.php')) {
        header('Location: install.php');
        exit;
    }
    throw $e;
}
