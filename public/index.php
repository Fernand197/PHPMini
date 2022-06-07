<?php

use Router\Router;
use App\Http\Controllers\HomeController;
use App\Http\Requests\Request;
use App\Models\User;

require '../vendor/autoload.php';

define('VIEWS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' .  DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);
define('SCRIPTS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);
define('DB', 'pgsql');
define('DB_NAME', 'blog');
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'postgres');
define('DB_PWD', 'Presir@197');
define('SENDER', 'testapplication197@gmail.com');

$router  = new Router();

$router->get('/404', 'App\Controllers\HomeController@error404');
$router->get('/', 'App\Controllers\HomeController@welcome');
$router->get('/welcome', [HomeController::class, 'welcome']);
$router->get('/user/:user', function (Request $request, $user) {
    echo "My name is $user->username and I'm 22 years old";
    // var_dump($request);
}, "test");
// echo $router->generate("test", ['fernand', 15]);
$router->run();
