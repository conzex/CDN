<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Polyfill mbstring functions if extension disabled on server
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) {
        return preg_split('/' . $pattern . '/u', $string, $limit);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) {
        return strlen($string);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) {
        return strtolower($string);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null) {
        return strtoupper($string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if (file_exists(base_path('install.php'))) {
                return redirect()->to('install.php?error=db');
            }
        });
        $exceptions->render(function (\PDOException $e, $request) {
            if (file_exists(base_path('install.php'))) {
                return redirect()->to('install.php?error=db');
            }
        });
    })->create();

// Support standalone root deployment where index.php is in base path
if (file_exists(dirname(__DIR__).'/index.php') && !is_dir(dirname(__DIR__).'/public/index.php')) {
    $app->usePublicPath(dirname(__DIR__));
}

return $app;
