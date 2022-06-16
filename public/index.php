<?php


use PHPMini\Application\Application;
use Router\Router;
use App\Http\Controllers\HomeController;
use App\Http\Requests\Request;
use App\Models\User;
use Router\Route;

require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

define('VIEWS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' .  DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);
define('SCRIPTS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);


$router  = new Router();

$router->controller(HomeController::class)->group(function () use ($router) {
    $router->get('/404', 'error404')->name('error.404');
    $router->get('welcome/{user}', 'welcome')->name('welcome');
});
// $router->get('/404', 'App\Http\Controllers\HomeController@error404');
// $router->api()->group(function () use ($router) {
//     $router->get('/welcome/{user}', [HomeController::class, "welcome"]);
//     $router->get('/', 'App\Http\Controllers\HomeController@welcome');
//     $router->get('/user/{user}', function (User $user) use ($router) {
//         var_dump($router) or die;
//         return view('welcome', compact('user'));
//     });
// });
// $router->scope('/api', function () use ($router) {
//     $router->get('/product/(\d+)', function (int $id) {
//         echo $id;
//     });
// });
$router->get('/product/(\d+)/tag/(\w+)', function (int $id, string $tag) use ($router) {
    var_dump("Product id: " . $id . " with tag: " . $tag) or die;
    $user = User::find($id);
    return view('welcome', compact('user'));
});
// echo $router->generate("welcome", [1]);
//$router->run();
(new Application($router))->run();
