<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Skill;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based sparring partner suggestions. Every point comes from a named reason,
 * so the ranking stays explainable:
 *
 *  level        same 3 · one step apart 2 · further 0 · unknown 1
 *  skills       +1 per shared skill with levels at most one step apart (max 3)
 *  availability 3 when both share a weekly window of at least 60 minutes
 *  weight       ≤ 5 kg 2 · ≤ 10 kg 1 · more −1 · unknown 0
 *  variety      −1 when the pair already sparred twice in the last 30 days
 */
class SparringMatcher
{
    public const MAX_POINTS = 11;

    private const MIN_OVERLAP_MINUTES = 60;
    private const MAX_SKILL_POINTS = 3;
    private const RECENT_DAYS = 30;
    private const RECENT_LIMIT = 2;

    /**
     * @return Collection<int, array{partner: User, points: int, reasons: array, slot: ?array}>
     */
    public function suggestionsFor(User $athlete, Team $team, int $limit = 3): Collection
    {
        $with = [
            'userDetail',
            'availabilities',
            'skills' => fn ($skills) => $skills->where('team_id', $team->id),
        ];
        $athlete->loadMissing($with);
        $candidates = $team->activeAthletes()->with($with)->where('users.id', '!=', $athlete->id)->get();
        $recent = $this->recentSparringCounts($athlete, $team);

        return $candidates
            ->map(fn (User $partner) => $this->score($athlete, $partner, $recent[$partner->id] ?? 0))
            ->sortBy([['points', 'desc'], ['partner.first_name', 'asc']])
            ->take($limit)
            ->values();
    }

    private function score(User $athlete, User $partner, int $recentCount): array
    {
        $reasons = [];
        $points = 0;

        $add = function (int $value, string $label) use (&$points, &$reasons) {
            $points += $value;
            $reasons[] = ['label' => $label, 'points' => $value];
        };

        $add(...$this->levelPoints($athlete, $partner));
        $add(...$this->skillPoints($athlete, $partner));
        $slot = $this->bestCommonSlot($athlete, $partner);
        if ($slot) {
            $day = substr(Availability::WEEKDAYS[$slot['weekday']], 0, 3);
            $add(3, "Both free {$day} {$slot['start']}–{$slot['end']}");
        } else {
            $add(0, 'No common time slot');
        }
        $add(...$this->weightPoints($athlete, $partner));
        if ($recentCount >= self::RECENT_LIMIT) {
            $add(-1, "Sparred together {$recentCount}× in the last " . self::RECENT_DAYS . ' days');
        }

        return [
            'partner' => $partner,
            'points'  => $points,
            'reasons' => $reasons,
            'slot'    => $slot ? $slot + ['date' => $this->nextDate($slot)] : null,
        ];
    }

    private function levelPoints(User $athlete, User $partner): array
    {
        $order = array_keys(Skill::LEVELS);
        $mine = array_search($athlete->userDetail?->experience_level, $order, true);
        $theirs = array_search($partner->userDetail?->experience_level, $order, true);
        if ($mine === false || $theirs === false) {
            return [1, 'Experience level not set'];
        }
        $label = Skill::LEVELS[$order[$theirs]];
        return match (abs($mine - $theirs)) {
            0 => [3, "Same level ($label)"],
            1 => [2, "Close level ($label)"],
            default => [0, "Different level ($label)"],
        };
    }

    private function skillPoints(User $athlete, User $partner): array
    {
        $order = array_flip(array_keys(Skill::LEVELS));
        $partnerLevels = $partner->skills->keyBy('id');
        $shared = $athlete->skills
            ->filter(fn ($skill) => $partnerLevels->has($skill->id)
                && abs($order[$skill->pivot->level] - $order[$partnerLevels[$skill->id]->pivot->level]) <= 1)
            ->pluck('name');
        if ($shared->isEmpty()) {
            return [0, 'No shared skills'];
        }
        return [min(self::MAX_SKILL_POINTS, $shared->count()), 'Shared skills: ' . $shared->implode(', ')];
    }

    private function weightPoints(User $athlete, User $partner): array
    {
        $mine = $athlete->userDetail?->weight;
        $theirs = $partner->userDetail?->weight;
        if (!$mine || !$theirs) {
            return [0, 'Weight unknown'];
        }
        $difference = round(abs($mine - $theirs), 1);
        return match (true) {
            $difference <= 5 => [2, "{$difference} kg weight difference"],
            $difference <= 10 => [1, "{$difference} kg weight difference"],
            default => [-1, "{$difference} kg weight difference"],
        };
    }

    /**
     * Longest overlap of two people's weekly availability, if it is long enough to spar.
     */
    private function bestCommonSlot(User $athlete, User $partner): ?array
    {
        $best = null;
        foreach ($athlete->availabilities as $mine) {
            foreach ($partner->availabilities->where('weekday', $mine->weekday) as $theirs) {
                $start = max($mine->start, $theirs->start);
                $end = min($mine->end, $theirs->end);
                $minutes = $this->minutes($end) - $this->minutes($start);
                if ($minutes >= self::MIN_OVERLAP_MINUTES && (!$best || $minutes > $best['minutes'])) {
                    $best = ['weekday' => $mine->weekday, 'start' => $start, 'end' => $end, 'minutes' => $minutes];
                }
            }
        }
        return $best;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int) $hours * 60 + (int) $minutes;
    }

    /**
     * Next calendar date for a weekly slot; today counts only if the slot has not started yet.
     */
    private function nextDate(array $slot): Carbon
    {
        $now = Carbon::now();
        $date = $now->copy()->startOfDay();
        $startedToday = fn () => $date->isToday() && $now->format('H:i') >= $slot['start'];
        while ($date->dayOfWeekIso !== $slot['weekday'] || $startedToday()) {
            $date->addDay();
        }
        return $date;
    }

    /**
     * How often the athlete sparred with each teammate in the last days (cancelled sessions excluded).
     */
    private function recentSparringCounts(User $athlete, Team $team): array
    {
        $since = Carbon::today()->subDays(self::RECENT_DAYS)->toDateString();
        return DB::table('schedule_participant as mine')
            ->join('schedule_participant as theirs', function ($join) use ($athlete) {
                $join->on('theirs.schedule_id', '=', 'mine.schedule_id')
                    ->where('theirs.user_id', '!=', $athlete->id);
            })
            ->join('schedules', 'schedules.id', '=', 'mine.schedule_id')
            ->where('mine.user_id', $athlete->id)
            ->where('schedules.team_id', $team->id)
            ->where('schedules.status', '!=', 'cancelled')
            ->where('schedules.date', '>=', $since)
            ->where('schedules.date', '<=', Carbon::today()->toDateString())
            ->groupBy('theirs.user_id')
            ->selectRaw('theirs.user_id as partner_id, COUNT(*) as total')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->partner_id => (int) $row->total])
            ->all();
    }
}
