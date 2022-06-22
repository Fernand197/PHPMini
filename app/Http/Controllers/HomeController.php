<?php

namespace App\Http\Controllers;

use App\Models\User;
use PHPMini\Requests\Request;

class HomeController extends Controller
{
    public function error404()
    {
        return view('errors.error404');
    }
    public function welcome($user, $id)
    {
        $users = User::where('id', '>=', 8)
            ->orWhere('id', 1)
            ->limit(5)
            ->get();
//        $uri = route("home.welcome", [1, 2]);
        dd($user, $id);
        return view('welcome', compact('user'));
    }
    
    public function index()
    {
        $users = User::all();
        dd($users);
    }
    
    public function show(Request $request, User $user)
    {
        var_dump($user->username, $user);
    }
    
    public function store(Request $request)
    {
        var_dump($request);
    }
    
    public function update(Request $request, User $user)
    {
        var_dump($user, $request->all());
    }
    
    public function delete(Request $request, User $user)
    {
        var_dump("user $user->username deleted");
    }
}

