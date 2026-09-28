<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            Schema::defaultStringLength(191);
        } catch (\Throwable $exception) {
            // Database may be unavailable during `composer install` (package:discover).
        }

        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Schedule::observe(\App\Observers\ScheduleObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        $teamActivity = new \App\Observers\TeamActivityObserver();
        \App\Models\Team::created(fn ($team) => $teamActivity->teamCreated($team));
        \App\Models\Notification::created(fn ($post) => $teamActivity->postCreated($post));
        \App\Models\TeamMembership::created(fn ($membership) => $teamActivity->membershipCreated($membership));
        \App\Models\TeamMembership::updated(fn ($membership) => $teamActivity->membershipUpdated($membership));
        \App\Models\TeamMembership::deleted(fn ($membership) => $teamActivity->membershipDeleted($membership));

        View::composer('frontend.includes.command_palette', \App\View\Composers\CommandPaletteComposer::class);

        View::composer('frontend.includes.header', function ($view) {
            $user = Auth::user();
            $team = $user?->currentTeam();
            $view->with([
                'headerReminders' => $team ? app(\App\Services\Reminders::class)->for($user, $team) : null,
                'headerCurrentTeam' => $team,
                'headerTeams' => $user ? $user->teams()->orderBy('name')->get(['id', 'name']) : collect(),
            ]);
        });
    }
}
