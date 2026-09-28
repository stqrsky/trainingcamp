<?php

namespace App\Http\Libraries;

use App\Models\Schedule;
use App\Models\Skill;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Profile data shared by the member form and the own profile form:
 * experience level, skill levels from the team's catalog and weekly availability.
 */
class MemberProfile
{
    private const MAX_AVAILABILITY_SLOTS = 21;
    private const UPCOMING_LIMIT = 3;

    public static function rules(): array
    {
        $levels = array_keys(Skill::LEVELS);
        return [
            'experience_level'       => ['nullable', Rule::in($levels)],
            'skill_levels'           => ['nullable', 'array'],
            'skill_levels.*'         => ['nullable', Rule::in($levels)],
            'availability'           => ['nullable', 'array', 'max:' . self::MAX_AVAILABILITY_SLOTS],
            'availability.*.weekday' => ['required', 'integer', 'between:1,7'],
            'availability.*.start'   => ['required', 'date_format:H:i'],
            'availability.*.end'     => ['required', 'date_format:H:i', 'after:availability.*.start'],
        ];
    }

    public static function save(User $user, Request $request, $team): void
    {
        $user->userDetail()->updateOrCreate([], [
            'experience_level' => $request->input('experience_level') ?: null,
        ]);

        // Only this team's skills are touched; unknown ids (other teams, forged input) are ignored
        if ($team) {
            $teamSkillIds = $team->skills()->pluck('id')->all();
            $levels = collect($request->input('skill_levels', []))
                ->filter()
                ->only($teamSkillIds)
                ->map(fn ($level) => ['level' => $level]);
            $user->skills()->detach($teamSkillIds);
            $user->skills()->attach($levels->all());
        }

        // The form always sends the marker, so clearing every row removes all slots
        if ($request->has('availability_present')) {
            $user->availabilities()->delete();
            $user->availabilities()->createMany(collect($request->input('availability', []))
                ->map(fn ($slot) => [
                    'weekday' => (int) $slot['weekday'],
                    'start'   => $slot['start'],
                    'end'     => $slot['end'],
                ])->values()->all());
        }
    }

    /**
     * Task and sparring numbers for a profile, limited to the viewer's own teams.
     */
    public static function summary(User $user): array
    {
        $teamIds = Auth::user()->teams()->pluck('id');
        $tasks = Task::whereIn('team_id', $teamIds)->where('assignee_id', $user->id);
        $sparrings = Schedule::whereIn('team_id', $teamIds)
            ->whereHas('participants', fn ($participants) => $participants->where('users.id', $user->id));

        $upcoming = (clone $sparrings)->active()->where('status', '!=', 'completed')
            ->whereDate('date', '>=', Carbon::today()->toDateString());

        return [
            'open_tasks'          => (clone $tasks)->open()->count(),
            'completed_tasks'     => (clone $tasks)->where('status', 'done')->count(),
            'completed_sparrings' => (clone $sparrings)->where('status', 'completed')->count(),
            'upcoming_count'      => (clone $upcoming)->count(),
            'upcoming_sparrings'  => (clone $upcoming)->with('participants')->orderBy('date')->orderBy('start')
                ->limit(self::UPCOMING_LIMIT)->get(),
        ];
    }
}
