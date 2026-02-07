<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Skill;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Http\Libraries\UploadImage;

class UserController extends Controller
{
    public function login()
    {
        return view('backend.login', ['title' => 'Sign In']);
    }

    public function loginUser(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);

        try {
            $user = User::whereEmail($request->input('email'))->first();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        if ($user) {
            $password_check = \Hash::check($request->input('password'), $user->password);
            if ($password_check) {
                Auth::login($user);
                return redirect()->route('home');
            } else {
                return redirect()->back()->withErrors([
                    'error' => 'Please check your email or password again'
                ])->withInput();
            }
        } else {
            return redirect()->back()->withErrors([
                'error' => 'Please check your email or password again'
            ])->withInput();
        }
    }

    public function register()
    {
        return view('backend.signup', ['title' => 'Sign Up']);
    }

    public function registerUser(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|unique:users,email',
            'password' => 'required|confirmed'
        ]);
        try {
            $user = User::create([
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ]);
            $user->roles()->attach(Role::where('title', 'coach')->first());
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        Auth::login($user);
        return redirect()->route('user.setting');
    }

    public function createProfile()
    {
        $skills = Skill::get();

        return view('frontend.users.createprofile', [
            'skills' => $skills
        ]);
    }

    private function validateProfileUser()
    {
        return $this->validate(request(), [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'first_name' => 'required',
            'last_name' => 'required',
            'nick_name' => 'required',
            'date_of_birth' => 'required|date_format:d/m/Y',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
            'about' => '',
            'team' => 'required'
        ]);
    }

    public function createProfileUser(Request $request)
    {
        $this->validateProfileUser();
        DB::beginTransaction();
        try {
            $user = User::find(Auth::user()->id);
            $input = $request->input();
            $user->update([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name']
            ]);
            $dob = Carbon::createFromFormat('d/m/Y', $input['date_of_birth']);
            $user->userDetail()->create([
                'nick_name' => $input['nick_name'],
                'date_of_birth' => $dob,
                'weight' => $input['weight'],
                'height' => $input['height'],
                'about' => $input['about']
            ]);
            $user->skills()->sync($input['skills']);
            $team = $user->team()->create([
                'name' => $input['team']
            ]);
            $team->coaches()->sync($user);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $upload_image = UploadImage::uploadProfilePicture($file, $user);
            if (isset($upload_image['error'])) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
            }
        }
        DB::commit();
        return redirect()->route('home');
    }

    public function profile()
    {
        $user = User::with(['userDetail', 'userDetail.image', 'skills', 'team'])->find(Auth::user()->id);
        return view('frontend.users.profile', compact('user'));
    }

    public function profileSetting()
    {
        $user = User::with('skills')->find(Auth::user()->id);
        $team = $user->team;
        $detail = $user->userDetail;
        $skills = Skill::get();
        $skills = collect($user->skills)->merge($skills)->unique('id')->values();
        return view('frontend.users.profilesetting', compact('user', 'team', 'detail', 'skills'));
    }

    public function updateProfile(Request $request)
    {
        $this->validateProfileUser();
        DB::beginTransaction();
        try {
            $user = User::find(Auth::user()->id);
            $input = $request->input();
            $user->update([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name']
            ]);
            $dob = Carbon::createFromFormat('d/m/Y', $input['date_of_birth']);
            UserDetail::updateOrCreate(
                [
                    'user_id' => $user->id
                ],
                [
                    'nick_name' => $input['nick_name'],
                    'date_of_birth' => $dob,
                    'weight' => $input['weight'],
                    'height' => $input['height'],
                    'about' => $input['about']
                ]
            );
            $user->skills()->sync($input['skills']);
            Team::updateOrCreate(
                [
                    'user_id' => $user->id
                ],
                [
                    'name' => $input['team']
                ]
            );
            $team = $user->team;
            $team->coaches()->sync($user);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $upload_image = UploadImage::uploadProfilePicture($file, $user);
            if (isset($upload_image['error'])) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
            }
        }
        DB::commit();
        $request->session()->flash('msg', 'Profile updated');
        return redirect()->route('user.profile');
    }

    public function accountSetting()
    {
        $user = User::find(Auth::user()->id);
        return view('frontend.users.accountsetting', compact('user'));
    }

    public function updateProfileAccount(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $this->validate($request, [
            'email' => 'sometimes|required|unique:users,email,' . $user->id,
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => 'sometimes|required|confirmed'
        ]);
        $email = $request->input('email');
        $current_password = $request->input('current_password');
        $new_password = $request->input('new_password');
        if (isset($email)) {
            $user->update([
                'email' => $email
            ]);
        }
        if (isset($new_password)) {
            // Check current password
            $password_check = \Hash::check($current_password, $user->password);
            if (!$password_check) {
                return redirect()->back()->withErrors(['current_password' => 'Invalid password'])->withInput();
            }
            // Check new password
            $new_password_check = \Hash::check($new_password, $user->password);
            if ($new_password_check) {
                return redirect()->back()->withErrors(['new_password' => 'New password must be different with current password'])->withInput();
            }
            $user->update([
                'password' => $new_password
            ]);
        }
        return redirect()->route('user.profile');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
