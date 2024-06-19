<?php

use App\Models\User;
use PHPMini\Routing\Router;
use App\Http\Controllers\HomeController;

$router = new Router();

/*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application.
    |
    */


$router->get('/', function () {
    echo "Hello world";
});
$router->get('/welcome/{user}/{id}', "App\Http\Controllers\HomeController@welcome");
$router->get('/product/(\d+)/tag/(\w+)', function (User $user, string $tag) {
    // var_dump("Product id: " . $user->id . " with tag: " . $tag) or die;
    // $user = User::find($id);
    // dd($id);
    return view('welcome', compact('user'));
});








return $router;
