<?php

use App\Http\Kernel;
use PHPMini\Requests\Request;

ini_set("display_errors", 1);

/**
 * ------------------------------------------------------------------------
 * Register the Auto Loader
 * ------------------------------------------------------------------------
 * 
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * ------------------------------------------------------------------------
 * Run the Application
 * ------------------------------------------------------------------------
 * 
 * 
 */

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class, ["app" => $app]);

$kernel->handle(new Request);
