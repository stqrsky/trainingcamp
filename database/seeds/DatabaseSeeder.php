<?php

use Database\Seeders\DatabaseSeeder as LaravelDatabaseSeeder;
use Illuminate\Database\Seeder;

/**
 * @deprecated Use Database\Seeders\DatabaseSeeder via `php artisan db:seed`.
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(LaravelDatabaseSeeder::class);
    }
}
