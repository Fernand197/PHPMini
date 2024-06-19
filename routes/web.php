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

Route::get('/welcome/{user}', [HomeController::class, "welcome"]);

Route::get('/{user}', function (User $user) {
    echo "Hello World " . $user->email;
});
