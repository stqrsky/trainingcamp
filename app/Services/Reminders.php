<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Actionable reminders for the account holder, computed live from the active team.
 * Nothing is stored: a reminder disappears as soon as the task is done or the date passes.
 */
class Reminders
{
    public const TYPES = [
        'overdue_tasks'      => 'Overdue tasks',
        'due_today'          => 'Tasks due today',
        'upcoming_sparrings' => 'Sparrings in the next 24 hours',
    ];

    private const LIMIT = 10;

    /**
     * @return array{count: int, items: Collection}
     */
    public function for(User $user, Team $team): array
    {
        $items = collect();
        $today = Carbon::today();
        $openTasks = fn () => Task::where('team_id', $team->id)->open()->whereNotNull('due_date');

        if ($user->wantsReminder('overdue_tasks')) {
            $items = $items->concat($openTasks()->whereDate('due_date', '<', $today->toDateString())
                ->orderBy('due_date')->get()
                ->map(fn ($task) => [
                    'title'    => $task->title,
                    'subtitle' => 'Overdue since ' . Carbon::parse($task->due_date)->format('j M'),
                    'url'      => route('tasks.edit', $task),
                    'icon'     => 'warning',
                    'tone'     => 'danger',
                ]));
        }
        if ($user->wantsReminder('due_today')) {
            $items = $items->concat($openTasks()->whereDate('due_date', $today->toDateString())
                ->orderByPriority()->get()
                ->map(fn ($task) => [
                    'title'    => $task->title,
                    'subtitle' => 'Due today' . ($task->due_time ? ' at ' . substr($task->due_time, 0, 5) : ''),
                    'url'      => route('tasks.edit', $task),
                    'icon'     => 'task_alt',
                    'tone'     => 'warning',
                ]));
        }
        if ($user->wantsReminder('upcoming_sparrings')) {
            $items = $items->concat($this->upcomingSparrings($team)->map(fn ($schedule) => [
                'title'    => $schedule->title ?: 'Sparring',
                'subtitle' => (Carbon::parse($schedule->date)->isToday() ? 'Today' : 'Tomorrow')
                    . " at {$schedule->start} · " . $schedule->participants->pluck('full_name')->implode(' vs '),
                'url'      => route('schedules.index', ['date' => $schedule->date_format]),
                'icon'     => 'sports_kabaddi',
                'tone'     => 'info',
            ]));
        }

        return ['count' => $items->count(), 'items' => $items->take(self::LIMIT)->values()];
    }

    /**
     * Active sparrings starting within the next 24 hours (or running right now).
     */
    private function upcomingSparrings(Team $team): Collection
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay();
        return Schedule::with('participants')->where('team_id', $team->id)->active()
            ->where('status', '!=', 'completed')
            ->where(fn ($query) => $query
                ->where(fn ($today) => $today->whereDate('date', $now->toDateString())
                    ->where('end', '>=', $now->format('H:i')))
                ->orWhere(fn ($next) => $next->whereDate('date', $tomorrow->toDateString())
                    ->where('start', '<=', $tomorrow->format('H:i'))))
            ->orderBy('date')->orderBy('start')->get();
    }
}
