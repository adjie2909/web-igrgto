<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$subfolder = '/'.basename(dirname(__DIR__));
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

if (
    ($requestUri === $subfolder || str_starts_with($requestUri, $subfolder.'/'))
    && ! str_starts_with($requestUri, $subfolder.'/public')
) {
    $_SERVER['SCRIPT_NAME'] = $subfolder.'/index.php';
    $_SERVER['PHP_SELF'] = $subfolder.'/index.php';
}

$app->handleRequest(Request::capture());
