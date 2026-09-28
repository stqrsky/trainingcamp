<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Project;

/**
 * Records project creation, status changes and deletion in the team activity feed.
 */
class ProjectObserver
{
    public function created(Project $project): void
    {
        Activity::record($project->team_id, 'project.created', "created project “{$project->name}”", $project);
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged('status')) {
            $description = $project->status === 'completed'
                ? "completed project “{$project->name}”"
                : "set project “{$project->name}” to {$project->status_label}";
            Activity::record($project->team_id, 'project.status', $description, $project);
        }
    }

    public function deleted(Project $project): void
    {
        Activity::record($project->team_id, 'project.deleted', "deleted project “{$project->name}”");
    }
}
