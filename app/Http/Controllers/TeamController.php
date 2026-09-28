<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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
        $search = $request->input('search');
        $team = $this->currentTeam()?->load([
            'coaches',
            'coaches.userDetail',
            'coaches.userDetail.image',
            'athletes' => function ($athletes) use ($search) {
                $athletes->where('first_name', 'like', "%$search%");
            },
            'athletes.userDetail',
            'athletes.userDetail.image',
            'athletes.skills'
        ]);
        return view('frontend.athletes.athletes', compact('team', 'search'));
    }

    public function addUser()
    {
        $skills = Skill::get();
        return view('frontend.athletes.createathlete', compact('skills'));
    }

    public function createUser(Request $request)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $this->validate($request, [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'user_type' => 'required|in:coach,athlete',
            'email' => 'nullable|email:filter|unique:users,email',
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
                'login_enabled' => false,
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
                    return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to add this member.')])
                ->withInput();
        }
        DB::commit();
        return redirect()->route('user.athletes');
    }

    public function editUser($id)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
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
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
        }
        $this->validate($request, [
            'file' => 'mimes:jpg,jpeg,png|max:2048',
            'email' => 'nullable|email:filter|unique:users,email,' . $id,
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
                'about' => $input['about'] ?? null,
            ]);
            $user->skills()->sync($input['skills']);
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $upload_image = UploadImage::uploadProfilePicture($file, $user);
                if (isset($upload_image['error'])) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['error' => $upload_image['error']])->withInput();
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to update this member.')])
                ->withInput();
        }
        DB::commit();
        return redirect()->route('user.athletes');
    }

    public function deleteUser($id)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }
        try {
            $team->athletes()->detach($user);
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to remove this member.')]);
        }
        return redirect()->route('user.athletes');
    }

    public function detailUser($id)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $user = $team->athletes()->where('user_id', $id)->first();
        if (!$user) {
            $user = $team->coaches()->where('user_id', $id)->first();

            if (!$user) {
                return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
            }
        }
        return view('frontend.athletes.detail', compact('user', 'team'));
    }

    public function createTeam()
    {
        return view('frontend.teams.create');
    }

    public function storeTeam(Request $request)
    {
        $data = $this->validateTeam($request);
        $user = Auth::user();
        DB::transaction(function () use ($user, $data) {
            $team = $user->teams()->create($data);
            $team->coaches()->attach($user);
            $user->switchTeam($team);
        });
        return redirect()->route('user.athletes');
    }

    public function editTeam()
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        return view('frontend.teams.edit', compact('team'));
    }

    public function updateTeam(Request $request)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $team->update($this->validateTeam($request));
        return redirect()->route('user.athletes');
    }

    public function switchTeam(Team $team)
    {
        $user = Auth::user();
        abort_unless($team->user()->is($user), 404);
        $user->switchTeam($team);
        return redirect()->back();
    }

    private function validateTeam(Request $request): array
    {
        return $this->validate($request, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
    }
}
