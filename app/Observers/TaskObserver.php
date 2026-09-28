<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

/**
 * Records relevant task changes (not every edit) in the team activity feed.
 */
class TaskObserver
{
    public function created(Task $task): void
    {
        Activity::record($task->team_id, 'task.created', "created task “{$task->title}”", $task);
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('status')) {
            $description = match (true) {
                $task->isDone() => "completed task “{$task->title}”",
                $task->getOriginal('status') === 'done' => "reopened task “{$task->title}”",
                default => "moved task “{$task->title}” to {$task->status_label}",
            };
            Activity::record($task->team_id, 'task.status', $description, $task);
        }
        if ($task->wasChanged('assignee_id')) {
            $assignee = $task->assignee_id ? User::find($task->assignee_id)?->full_name : null;
            Activity::record($task->team_id, 'task.assigned', $assignee
                ? "assigned task “{$task->title}” to {$assignee}"
                : "unassigned task “{$task->title}”", $task);
        }
        if ($task->wasChanged('due_date')) {
            Activity::record($task->team_id, 'task.deadline', $task->due_date
                ? "changed the deadline of “{$task->title}” to " . Carbon::parse($task->due_date)->format('j M')
                : "removed the deadline of “{$task->title}”", $task);
        }
    }

    public function deleted(Task $task): void
    {
        Activity::record($task->team_id, 'task.deleted', "deleted task “{$task->title}”");
    }
}
