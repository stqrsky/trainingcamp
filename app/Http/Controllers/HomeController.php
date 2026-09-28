<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class HomeController extends Controller
{
    public function index()
    {
        $team = $this->currentTeam();
        $notifications = Notification::with([
            'user',
            'user.userDetail',
            'user.userDetail.image',
            'image'
        ])->where('user_id', Auth::id())
            ->where(function ($query) use ($team) {
                $query->where('team_id', $team?->id)->orWhereNull('team_id');
            })
            ->orderByDesc('created_at')->paginate(12);
        return view('frontend.home', compact('notifications'));
    }
}
