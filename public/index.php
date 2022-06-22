<?php

define('VIEWS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' .  DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);
define('SCRIPTS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);


require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();



$app = require "../bootstrap/app.php";


$app->run();
