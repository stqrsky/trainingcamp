<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Notification;

class HomeController extends Controller
{
    public function index()
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $notifications = Notification::with([
            'user',
            'user.userDetail',
            'user.userDetail.image',
            'image'
        ])->where('user_id', $profile->id)->orderByDesc('created_at')->paginate(12);
        return view('frontend.home', compact('notifications'));
    }
}
