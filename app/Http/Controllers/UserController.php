<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Skill;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Http\Libraries\UploadImage;
use App\Http\Libraries\MemberProfile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function login()
    {
        return view('backend.login', ['title' => 'Sign In']);
    }

    public function loginUser(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email:filter',
            'password' => 'required',
        ]);

        $throttle_key = Str::lower($request->input('email')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttle_key, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttle_key);
            return redirect()->back()
                ->withErrors(['error' => "Too many login attempts. Please try again in $seconds seconds."])
                ->withInput($request->only('email'));
        }

        try {
            $user = User::whereEmail($request->input('email'))->first();
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($e, 'Unable to sign in right now.')])
                ->withInput($request->only('email'));
        }

        // Team members are managed profiles; only account holders may sign in.
        if (!$user || !$user->login_enabled || !\Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttle_key);
            return redirect()->back()->withErrors([
                'error' => 'Please check your email or password again'
            ])->withInput($request->only('email'));
        }

        RateLimiter::clear($throttle_key);
        Auth::login($user);

        return redirect()->route('home');
    }

    public function register()
    {
        return view('backend.signup', ['title' => 'Sign Up']);
    }

    public function registerUser(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email:filter|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $coachRole = Role::where('title', 'coach')->first();
        if (!$coachRole) {
            return redirect()->back()
                ->withErrors(['error' => 'Application roles are missing. Run: php artisan migrate --seed'])
                ->withInput();
        }

        try {
            $user = User::create([
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'login_enabled' => true,
            ]);
            $user->roles()->attach($coachRole->id);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($e, 'Unable to create your account.')])
                ->withInput($request->only('email'));
        }

        Auth::login($user);

        return redirect()->route('user.setting');
    }

    public function createProfile()
    {
        if (Auth::user()->currentTeam()) {
            return redirect()->route('home');
        }
        return view('frontend.users.createprofile');
    }

    private function validateProfileUser(bool $withTeam = false)
    {
        $rules = [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'first_name' => 'required',
            'last_name' => 'required',
            'nick_name' => 'required',
            'date_of_birth' => 'required|date_format:d/m/Y',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'about' => '',
        ] + MemberProfile::rules();
        if ($withTeam) {
            $rules['team'] = 'required|string|max:255';
        }
        return $this->validate(request(), $rules);
    }

    public function createProfileUser(Request $request)
    {
        if (Auth::user()->currentTeam()) {
            return redirect()->route('home');
        }
        $this->validateProfileUser(true);
        DB::beginTransaction();
        try {
            $user = User::find(Auth::user()->id);
            $input = $request->input();
            $user->update([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name']
            ]);
            $dob = Carbon::createFromFormat('d/m/Y', $input['date_of_birth']);
            $user->userDetail()->updateOrCreate([], [
                'nick_name' => $input['nick_name'],
                'date_of_birth' => $dob,
                'weight' => $input['weight'],
                'height' => $input['height'],
                'about' => $input['about'] ?? null
            ]);
            $team = $user->teams()->create([
                'name' => $input['team']
            ]);
            $team->coaches()->sync($user);
            $user->switchTeam($team);
            MemberProfile::save($user, $request, $team);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($e, 'Unable to save your profile.')])
                ->withInput();
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
        $team = $this->currentTeam();
        $user = User::with([
            'userDetail.image',
            'teams',
            'availabilities',
            'skills' => fn ($skills) => $skills->where('team_id', $team?->id),
        ])->find(Auth::user()->id);
        $summary = MemberProfile::summary($user);
        return view('frontend.users.profile', compact('user', 'summary'));
    }

    public function profileSetting()
    {
        $user = User::with(['skills', 'availabilities'])->find(Auth::user()->id);
        $detail = $user->userDetail;
        $teamSkills = $this->currentTeam()?->skills ?? collect();
        return view('frontend.users.profilesetting', compact('user', 'detail', 'teamSkills'));
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
                    'about' => $input['about'] ?? null
                ]
            );
            MemberProfile::save($user, $request, $this->currentTeam());
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($e, 'Unable to save your profile.')])
                ->withInput();
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
            'email' => 'sometimes|required|email:filter|unique:users,email,' . $user->id,
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
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
                return redirect()->back()->withErrors(['current_password' => 'Invalid password'])
                    ->withInput($request->only('email'));
            }
            // Check new password
            $new_password_check = \Hash::check($new_password, $user->password);
            if ($new_password_check) {
                return redirect()->back()
                    ->withErrors(['new_password' => 'New password must be different with current password'])
                    ->withInput($request->only('email'));
            }
            $user->update([
                'password' => $new_password
            ]);
        }
        return redirect()->route('user.profile');
    }

    public function notificationSettings()
    {
        $user = Auth::user();
        $types = \App\Services\Reminders::TYPES;
        return view('frontend.users.notifications', compact('user', 'types'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $types = array_keys(\App\Services\Reminders::TYPES);
        $this->validate($request, [
            'reminders' => 'nullable|array',
            'reminders.*' => 'in:1',
            'daily_digest' => 'nullable|in:1',
        ]);
        $enabled = array_keys($request->input('reminders', []));
        $preferences = collect($types)->mapWithKeys(fn ($type) => [$type => in_array($type, $enabled, true)]);
        $preferences['daily_digest'] = $request->boolean('daily_digest');
        Auth::user()->update(['notification_preferences' => $preferences->all()]);
        $request->session()->flash('msg', 'Reminder settings saved');
        return redirect()->route('user.notifications');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
