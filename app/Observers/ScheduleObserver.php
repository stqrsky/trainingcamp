<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Schedule;
use Carbon\Carbon;

/**
 * Records sparring planning, status changes and rescheduling in the team activity feed.
 */
class ScheduleObserver
{
    private const STATUS_VERBS = [
        'planned'     => 'set back to planned',
        'confirmed'   => 'confirmed',
        'in_progress' => 'started',
        'completed'   => 'completed',
        'cancelled'   => 'cancelled',
    ];

    public function created(Schedule $schedule): void
    {
        $description = "planned {$this->name($schedule)} for {$this->when($schedule)}";
        Activity::record($schedule->team_id, 'sparring.created', $description, $schedule);
    }

    public function updated(Schedule $schedule): void
    {
        if ($schedule->wasChanged('status')) {
            $verb = self::STATUS_VERBS[$schedule->status] ?? 'updated';
            Activity::record($schedule->team_id, 'sparring.status', "{$verb} {$this->name($schedule)}", $schedule);
        }
        if ($schedule->wasChanged('date') || $schedule->wasChanged('start')) {
            $description = "rescheduled {$this->name($schedule)} to {$this->when($schedule)}";
            Activity::record($schedule->team_id, 'sparring.rescheduled', $description, $schedule);
        }
    }

    public function deleted(Schedule $schedule): void
    {
        $description = "deleted {$this->name($schedule)} of {$this->when($schedule)}";
        Activity::record($schedule->team_id, 'sparring.deleted', $description);
    }

    private function name(Schedule $schedule): string
    {
        return $schedule->title ? "sparring “{$schedule->title}”" : 'a sparring';
    }

    private function when(Schedule $schedule): string
    {
        return Carbon::parse($schedule->date)->format('D j M') . ' ' . $schedule->start;
    }
}
