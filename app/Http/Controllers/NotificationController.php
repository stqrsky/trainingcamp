<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function create()
    {
        return view('frontend.notifications.create');
    }

    public function createNotification()
    {
        return redirect()->route('home');
    }

    public function edit()
    {
        return view('frontend.notifications.edit');
    }

    public function editNotification($id)
    {
        return redirect()->route('home');
    }

    public function deleteNotification($id)
    {
        return redirect()->route('home');
    }
}
