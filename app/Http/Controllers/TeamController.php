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
use App\Http\Libraries\MemberProfile;
use Illuminate\Validation\Rule;
use App\Services\SparringMatcher;

class TeamController extends Controller
{
    private const MEMBER_SORTS = ['name', 'name_desc', 'newest'];

    public function getUserTeam(Request $request)
    {
        $filters = $this->memberFilters($request);
        $constrain = fn ($members) => $this->applyMemberFilters($members, $filters);
        $team = $this->currentTeam()?->load([
            'coaches' => $constrain,
            'coaches.userDetail.image',
            'coaches.skills',
            'athletes' => $constrain,
            'athletes.userDetail.image',
            'athletes.skills',
        ]);
        $skills = $team?->skills ?? collect();
        $search = $filters['search'];
        return view('frontend.athletes.athletes', compact('team', 'search', 'filters', 'skills'));
    }

    private function memberFilters(Request $request): array
    {
        $pick = fn ($key, array $allowed, $default) => in_array($request->query($key), $allowed, true)
            ? $request->query($key)
            : $default;
        return [
            'search' => trim((string) $request->query('search', '')),
            'role'   => $pick('role', ['coach', 'athlete'], 'all'),
            'skill'  => (int) $request->query('skill') ?: null,
            'status' => $pick('status', ['inactive', 'all'], 'active'),
            'sort'   => $pick('sort', self::MEMBER_SORTS, 'name'),
        ];
    }

    private function applyMemberFilters($members, array $filters): void
    {
        $members->matchingName($filters['search']);
        if ($filters['skill']) {
            $members->whereHas('skills', fn ($skills) => $skills->where('skills.id', $filters['skill']));
        }
        if ($filters['status'] !== 'all') {
            $members->wherePivot('active', $filters['status'] === 'active');
        }
        match ($filters['sort']) {
            'name_desc' => $members->orderByDesc('users.first_name')->orderByDesc('users.last_name'),
            'newest'    => $members->orderByPivot('id', 'desc'),
            default     => $members->orderBy('users.first_name')->orderBy('users.last_name'),
        };
    }

    public function addUser()
    {
        $teamSkills = $this->currentTeam()?->skills ?? collect();
        return view('frontend.athletes.createathlete', compact('teamSkills'));
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
            'about' => '',
        ] + MemberProfile::rules());
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
            MemberProfile::save($user, $request, $team);
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
        $user = $this->findMember($team, $id);
        if (!$user || $user->is(Auth::user())) {
            return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
        }
        $detail = $user->userDetail;
        $teamSkills = $team->skills;
        return view('frontend.athletes.editathlete', compact('user', 'detail', 'teamSkills'));
    }

    public function updateUser(Request $request, $id)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $user = $this->findMember($team, $id);
        if (!$user || $user->is(Auth::user())) {
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
            'about' => '',
        ] + MemberProfile::rules());
        DB::beginTransaction();
        $input = $request->all();
        try {
            $user->update([
                'email' => $input['email'] ?? null,
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
            MemberProfile::save($user, $request, $team);
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
        $user = $this->findMember($team, $id);
        if (!$user || $user->is(Auth::user())) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }
        try {
            $team->athletes()->detach($user);
            $team->coaches()->detach($user);
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
        $user = $this->findMember($team, $id);
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found'])->withInput();
        }
        $user->load(['skills' => fn ($skills) => $skills->where('team_id', $team->id), 'availabilities']);
        $summary = MemberProfile::summary($user);
        $matches = $team->activeAthletes()->where('users.id', $user->id)->exists()
            ? app(SparringMatcher::class)->suggestionsFor($user, $team)
            : null;
        return view('frontend.athletes.detail', compact('user', 'team', 'summary', 'matches'));
    }

    /**
     * Active members against the team's skills, for sparring and training planning.
     */
    public function skillMatrix()
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $skills = $team->skills;
        $withSkills = [
            'userDetail',
            'skills' => fn ($query) => $query->where('team_id', $team->id),
        ];
        $rows = fn ($relation, string $role) => $relation->with($withSkills)->orderBy('first_name')->get()
            ->map(fn ($member) => ['member' => $member, 'role' => $role]);
        $members = $rows($team->activeAthletes(), 'Athlete')
            ->concat($rows($team->activeCoaches(), 'Coach'))
            ->unique('member.id')->values();
        return view('frontend.athletes.matrix', compact('team', 'skills', 'members'));
    }

    /**
     * Set a member active or inactive in the current team; inactive members keep their history.
     */
    public function toggleMemberStatus($id)
    {
        $team = $this->currentTeam();
        $user = $team ? $this->findMember($team, $id) : null;
        if (!$user || $user->is(Auth::user())) {
            abort(404);
        }
        $relation = $user->pivot->getTable() === 'team_coach' ? $team->coaches() : $team->athletes();
        $relation->updateExistingPivot($user->id, ['active' => !$user->pivot->active]);
        return redirect()->back();
    }

    /**
     * A coach or athlete of the given team, with its membership pivot loaded.
     */
    private function findMember($team, $id): ?User
    {
        return $team->athletes()->with(['userDetail', 'userDetail.image'])->where('users.id', $id)->first()
            ?? $team->coaches()->with(['userDetail', 'userDetail.image'])->where('users.id', $id)->first();
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

    public function addSkill(Request $request)
    {
        $team = $this->currentTeam();
        abort_unless($team, 404);
        $this->validate($request, [
            'skill_name' => [
                'required', 'string', 'max:50',
                Rule::unique('skills', 'name')->where('team_id', $team->id),
            ],
        ], [], ['skill_name' => 'skill']);
        $team->skills()->create(['name' => trim($request->input('skill_name')), 'status' => 1]);
        return redirect()->route('teams.edit');
    }

    public function removeSkill(Skill $skill)
    {
        $team = $this->currentTeam();
        abort_unless($team && $skill->team_id === $team->id, 404);
        DB::transaction(function () use ($skill) {
            $skill->users()->detach();
            $skill->delete();
        });
        return redirect()->route('teams.edit');
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
