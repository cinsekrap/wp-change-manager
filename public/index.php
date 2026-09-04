<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// If no .env exists, write one from the install bootstrap so Laravel can boot.
//
// The key is generated here rather than shipped. A key committed to the
// repository is not a key: it would sign this installation's URLs and encrypt
// its cookies while being readable by anyone with the source.
if (!file_exists($env = __DIR__.'/../.env') && file_exists($template = __DIR__.'/../.env.install')) {
    $contents = file_get_contents($template);
    $key = 'base64:'.base64_encode(random_bytes(32));

    $contents = preg_match('/^APP_KEY=.*$/m', $contents)
        ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents)
        : rtrim($contents, "\n")."\nAPP_KEY=".$key."\n";

    file_put_contents($env, $contents);
    @chmod($env, 0600);
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
