<?php

use App\Http\Controllers\HomeController;
use App\Models\User;


$router = require("../routes/web.php");
$router->api()->group(function () use ($router) {
    
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application.
    | You can change the base name route for API Routes. By default is "api"
    | To set it consider use $route->api("your_basename_api_route") in the line
    | above.
    |
    */
    
    $router->get('/welcome/{user}/{post}', [HomeController::class, "welcome"])->name("home.welcome");
    
    $router->get('', 'App\Http\Controllers\HomeController@welcome');
    
    $router->get('/user/{user}/{id}', function (User $user, int $id) {
     var_dump($user, $id) or die;
     return view('welcome', compact('user'));
    });















});
return $router;