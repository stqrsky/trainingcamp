<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Static commands of the command palette (Cmd/Ctrl+K); search results come from SearchController.
 */
class CommandPaletteComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $command = fn (string $title, string $url, string $icon, string $keywords = '') =>
            compact('title', 'url', 'icon', 'keywords');
        $commands = collect([
            $command('New task', route('tasks.create'), 'add_task', 'create add todo'),
            $command('Plan sparring', route('schedules.create'), 'sports_kabaddi', 'create schedule session assign'),
            $command('Add member', route('user.athletes.create'), 'person_add', 'athlete coach new'),
            $command('New announcement', route('notification.create'), 'campaign', 'post news'),
            $command('Open calendar', route('schedules.month'), 'calendar_month', 'month schedule'),
            $command('Open agenda', route('schedules.agenda'), 'view_agenda', 'upcoming deadlines'),
            $command('Task board', route('tasks.index', ['view' => 'board']), 'view_kanban', 'kanban tasks'),
            $command('Projects', route('projects.index'), 'folder', 'progress overview'),
            $command('New project', route('projects.create'), 'create_new_folder', 'create add'),
            $command('My tasks', route('tasks.index', ['mine' => 1]), 'assignment_ind', 'assigned me'),
            $command('Team members', route('user.athletes'), 'groups', 'athletes coaches list'),
            $command('Skill matrix', route('user.athletes.matrix'), 'grid_view', 'skills levels overview'),
            $command('Team activity', route('activity'), 'history', 'feed log changes'),
            $command('Reminder settings', route('user.notifications'), 'notifications', 'notifications preferences'),
            $command('Edit team', route('teams.edit'), 'edit', 'skills settings rename'),
            $command('New team', route('teams.create'), 'group_add', 'create'),
        ]);

        if (\App\Services\Assistant\TeamAssistant::enabled()) {
            $commands->push($command('Ask the assistant', route('assistant'), 'auto_awesome', 'ai claude question'));
        }

        if ($user) {
            $currentId = $user->currentTeam()?->id;
            $user->teams()->orderBy('name')->get(['id', 'name'])
                ->reject(fn ($team) => $team->id === $currentId)
                ->each(fn ($team) => $commands->push([
                    'title'    => "Switch to {$team->name}",
                    'post'     => route('teams.switch', $team),
                    'icon'     => 'swap_horiz',
                    'keywords' => 'team change',
                ]));
        }

        $view->with('paletteCommands', $commands->values());
    }
}
