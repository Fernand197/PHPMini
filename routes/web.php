<?php

use App\Http\Controllers\HomeController;
use App\Models\User;
use PHPMini\Router\Router;

$router = new Router();

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application.
    |
    */


$router->controller(HomeController::class)->group(function () use ($router) {
    
    $router->get('/404', 'error404')->name('error.404');
    
    $router->get('welcome/{user}/{id}', 'welcome')->name('welcome');
});

$router->apiResource('/users', HomeController::class);

$router->get('/product/(\d+)/tag/(\w+)', function (int $id, string $tag) {
    var_dump("Product id: " . $id . " with tag: " . $tag) or die;
    $user = User::find($id);
    return view('welcome', compact('user'));
});








return $router;