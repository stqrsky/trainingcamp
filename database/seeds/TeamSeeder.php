<?php

use Database\Seeders\TeamSeeder as LaravelTeamSeeder;
use Illuminate\Database\Seeder;

/**
 * @deprecated Use Database\Seeders\TeamSeeder via `php artisan db:seed`.
 */
class TeamSeeder extends Seeder
{
    public function run()
    {
        $this->call(LaravelTeamSeeder::class);
    }
}
