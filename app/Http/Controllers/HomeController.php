<?php

namespace App\Http\Controllers;

use App\Models\User;
use PHPMini\Requests\Request;

class HomeController extends Controller
{

    public function __construct(private readonly Request $request)
    {
    }
    public function error404()
    {
        return view('errors.error404');
    }
    public function welcome(User $user)
    {
        $user->username = "presir";
        return view('welcome', compact('user'));
    }

    public function index()
    {
        $users = User::all();
        dd($users->filter(fn ($user) => $user->email_verified_at));
    }

    public function show(User $user, Request $request, int $number = 10)
    {
        dump($user, $this->request, $number);
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
