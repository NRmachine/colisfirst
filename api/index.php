<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

// Only compiled templates and framework manifests use temporary storage.
// Accounts and identity documents require durable external storage.
$compiled = getenv('VIEW_COMPILED_PATH');
if ($compiled && ! is_dir($compiled)) {
    mkdir($compiled, 0700, true);
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (getenv('VERCEL') === '1') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

$app->handleRequest(Request::capture());
