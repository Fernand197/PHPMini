<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Requests\Request;

class HomeController extends Controller
{
    public function error404()
    {
        return $this->view('errors/error404');
    }
    public function welcome()
    {
        $user = User::where([
            'username' => "Tores",
        ])->firstOr(function () {
            return User::find(1);
        });
        var_dump($user) or die;
        return $this->view('welcome');
    }
}
