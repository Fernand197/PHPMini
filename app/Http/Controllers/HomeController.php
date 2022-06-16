<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\Request;

class HomeController extends Controller
{
    public function error404()
    {
        return view('errors.error404');
    }
    public function welcome(User $user)
    {
        $users = User::where('id', '>=', 8)
            ->orWhere('id', 1)
            ->limit(5)
            ->get();
        dd($user);
        return view('welcome', compact('user'));
    }
}
