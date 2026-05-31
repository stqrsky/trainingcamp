<?php

use Database\Seeders\UserSeeder as LaravelUserSeeder;
use Illuminate\Database\Seeder;

/**
 * @deprecated Use Database\Seeders\UserSeeder via `php artisan db:seed`.
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        $this->call(LaravelUserSeeder::class);
    }
}
