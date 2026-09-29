<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Redirect to installer if .env does not exist or APP_KEY is empty
if (!file_exists(__DIR__.'/.env') && file_exists(__DIR__.'/install.php')) {
    header('Location: install.php');
    exit;
}

// Maintenance mode check
if (file_exists(__DIR__.'/storage/framework/maintenance.php')) {
    require __DIR__.'/storage/framework/maintenance.php';
}

// Register Composer Autoloader
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    die('Vendor autoloader missing. Please run "composer install" on your server.');
}

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// Set public path to current directory for cPanel root deployment
$app->usePublicPath(__DIR__);

// Handle request
$request = Request::capture();
$response = $app->handleRequest($request);
$response->send();
