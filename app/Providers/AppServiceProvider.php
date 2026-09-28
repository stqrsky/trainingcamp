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

        View::composer('frontend.includes.command_palette', \App\View\Composers\CommandPaletteComposer::class);

        View::composer('frontend.includes.header', function ($view) {
            $user = Auth::user();
            $view->with([
                'headerCurrentTeam' => $user?->currentTeam(),
                'headerTeams' => $user ? $user->teams()->orderBy('name')->get(['id', 'name']) : collect(),
            ]);
        });
    }
}
