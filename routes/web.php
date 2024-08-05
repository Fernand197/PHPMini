<?php

use App\Models\User;
use PHPMini\Facade\Route;
use App\Http\Controllers\HomeController;

/** 
 *--------------------------------------------------------------------------
 * Web Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register web routes for your application.
 *
 */

Route::get('welcome/{id}/{user}', [HomeController::class, "welcome"])->name('welcome');

Route::prefix('admin/')->name('users.')->group(function ($router) {
    Route::prefix('users')->group(function () {
        Route::get('/{user}/show', function (User $user) {
            echo "Hello World " . $user->email;
        })->name('show')->where('user', '[\w]+');
    });
});
Route::get('/{user}', function (User $user) {
    echo "Hello World " . $user->email;
});

Route::controller(HomeController::class)->group(function () {
    Route::get("/welcome", "index")->name('welcome.index');
});