<?php

use Database\Seeders\RoleSeeder as LaravelRoleSeeder;
use Illuminate\Database\Seeder;

/**
 * @deprecated Use Database\Seeders\RoleSeeder via `php artisan db:seed`.
 */
class RoleSeeder extends Seeder
{
    public function run()
    {
        $this->call(LaravelRoleSeeder::class);
    }
}
