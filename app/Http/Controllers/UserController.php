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
        return redirect()->route('home');
    }

    public function register()
    {
        return view('backend.signup', ['title' => 'Sign Up']);
    }

    public function registerUser()
    {
        return redirect()->route('user.setting');
    }

    public function createProfile()
    {
        return view('frontend.users.createprofile');
    }

    public function createProfileUser()
    {
        return redirect()->route('home');
    }

    public function getAthlete()
    {
        return view('frontend.athletes.athletes');
    }

    public function detailUser($id)
    {
        return view('frontend.athletes.editathlete');
    }

    public function updateUser($id)
    {
        return redirect()->route('user.athletes');
    }

    public function createUser()
    {
        return view('frontend.athletes.createathlete');
    }

    public function createUserData()
    {
        return redirect()->route('user.athletes');
    }

    public function profile()
    {
        return view('frontend.users.profile');
    }


    public function profileSetting()
    {
        return view('frontend.users.profilesetting');
    }

    public function accountSetting()
    {
        return view('frontend.users.accountsetting');
    }

    public function updateProfileAccount()
    {
        return redirect()->route('user.profile');
    }
}
