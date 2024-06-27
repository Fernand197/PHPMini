<?php

use PHPMini\Facade\Route;





/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| You can change the base name route for API Routes. By default is "api"
| To set it consider use  in the line
| above.
|
*/


Route::get("users/{user}", "App\Http\Controllers\HomeController@show");

Route::get('/', function () {
    dd(route('welcome', ['user' => 2, 1]));
});