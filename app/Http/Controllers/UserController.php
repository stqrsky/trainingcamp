<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class UserController extends Controller
{
    public function login()
    {
        return view('backend.login', ['title' => 'Sign In']);
    }

    public function loginUser(Request $request)
    {
    }

    public function register()
    {
    }

    public function registerUser()
    {
    }

    public function createProfile()
    {
    }

    public function createProfileUser()
    {
    }

    public function getAthlete()
    {
    }

    public function detailUser($id)
    {
    }

    public function updateUser($id)
    {
    }

    public function createUser()
    {
    }

    public function createUserData()
    {
    }

    public function profile()
    {
    }
}
