<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Libraries\UploadImage;

class NotificationController extends Controller
{
    public function create()
    {
        return view('frontend.notifications.create');
    }

    public function store(Request $request)
    {
        $profile = User::find(Auth::user()->id);
        $team = $this->currentTeam();
        $this->validate($request, [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'image' => '',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000'
        ]);
        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'user_id' => $profile->id,
                'team_id' => isset($team) ? $team->id : NULL,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to save this post.')])
                ->withInput();
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $upload_image = UploadImage::uploadNotificationPicture($file, $notification);
            if (isset($upload_image['error'])) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
            }
        }
        DB::commit();
        return redirect()->route('home');
    }

    public function edit($notification)
    {
        $profile = User::find(Auth::user()->id);
        $notification = Notification::with('image')->where('id', $notification)->where('user_id', $profile->id)->firstOrFail();
        return view('frontend.notifications.edit', compact('notification'));
    }

    public function update(Request $request, $notification)
    {
        $profile = User::find(Auth::user()->id);
        $notification = Notification::where('id', $notification)->where('user_id', $profile->id)->firstOrFail();
        $this->validate($request, [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'image' => '',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000'
        ]);
        DB::beginTransaction();
        try {
            $notification->update([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to save this post.')])
                ->withInput();
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $upload_image = UploadImage::uploadNotificationPicture($file, $notification);
            if (isset($upload_image['error'])) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
            }
        }
        DB::commit();
        return redirect()->route('home');
    }

    public function destroy($notification)
    {
        $profile = User::find(Auth::user()->id);
        $notification = Notification::where('id', $notification)->where('user_id', $profile->id)->firstOrFail();
        $notification->delete();
        return redirect()->route('home');
    }
}
