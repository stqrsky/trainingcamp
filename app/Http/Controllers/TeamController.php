<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Skill;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Libraries\UploadImage;

class TeamController extends Controller
{
    public function getUserTeam(Request $request)
    {
        $input = $request->input();
        $search = $request->input('search');
        $user = User::find(Auth::user()->id);
        $team = $user->team()->with([
            'coaches',
            'coaches.userDetail',
            'coaches.userDetail.image',
            'athletes' => function ($athletes) use ($search) {
                $athletes->where('first_name', 'like', "%$search%");
            },
            'athletes.userDetail',
            'athletes.userDetail.image',
            'athletes.skills'
        ])->first();
        return view('frontend.athletes.athletes', compact('team', 'search'));
    }

    public function addUser()
    {
        $skills = Skill::get();
        return view('frontend.athletes.createathlete', compact('skills'));
    }

    public function createUser(Request $request)
    {
        $profile = User::find(Auth::user()->id);
        $this->validate($request, [
            'file' => 'mimes:jpg,jpeg,png',
            'user_type' => 'required|in:coach,athlete',
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'nick_name' => 'required',
            'date_of_birth' => 'required|date_format:d/m/Y',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
            'about' => '',
        ]);
        $role = Role::where('title', 'like', '%' . $request->input('user_type') . '%')->first();
        if (!$role) {
            return redirect()->back()->withErrors(['error' => 'User type not found'])->withInput();
        }
        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ]);
            $dob = Carbon::createFromFormat('d/m/Y', $request->input('date_of_birth'))->format('Y-m-d');
            $user->userDetail()->create([
                'nick_name' => $request->input('nick_name'),
                'date_of_birth' => $dob,
                'weight' => $request->input('weight'),
                'height' => $request->input('height'),
                'about' => $request->input('about')
            ]);
            $user->skills()->attach($request->input('skills'));
            $user->roles()->attach($role);
            $team = Team::where('user_id', $profile->id)->first();
            if ($request->input('user_type') == 'coach') {
                $team->coaches()->attach($user);
            } else {
                $team->athletes()->attach($user);
            }
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $upload_image = UploadImage::uploadProfilePicture($file, $user);
                if (isset($upload_image['error'])) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        DB::commit();
        return redirect()->route('user.athletes');
    }

    public function editUser($id)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $user = $team->athletes()->with(['userDetail', 'userDetail.image'])
            ->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
        }
        $detail = $user->userDetail;
        $user_skills = $user->skills;
        $skills = Skill::get();
        $skills = collect($user_skills)->merge($skills)->unique('id')->all();
        return view('frontend.athletes.editathlete', compact('user', 'detail', 'skills'));
    }

    public function updateUser(Request $request, $id)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
        }
        $this->validate($request, [
            'email' => 'required|unique:users,email,' . $id,
            'first_name' => 'required',
            'last_name' => 'required',
            'nick_name' => 'required',
            'date_of_birth' => 'required|date_format:d/m/Y',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
            'about' => '',
        ]);
        DB::beginTransaction();
        $input = $request->all();
        try {
            $user->update([
                'email' => $input['email'],
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
            ]);
            $dob = Carbon::createFromFormat('d/m/Y', $input['date_of_birth']);
            $user->userDetail()->update([
                'nick_name' => $input['nick_name'],
                'date_of_birth' => $dob,
                'weight' => $input['weight'],
                'height' => $input['height'],
                'about' => $input['about'],
            ]);
            $user->skills()->sync($input['skills']);
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $upload_image = UploadImage::uploadProfilePicture($file, $user);
                if (isset($upload_image['error'])) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
        DB::commit();
        return redirect()->route('user.athletes');
    }

    public function deleteUser($id)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }
        try {
            $team->athletes()->detach($user);
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
        return redirect()->route('user.athletes');
    }

    public function detailUser($id)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            $user = $team->coaches()->where('user_id', $id)->first();

            if (!$user) {
                return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
            }
        }
        return view('frontend.athletes.detail', compact('user', 'team'));
    }
}
