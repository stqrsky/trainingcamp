<?php

use Database\Seeders\SkillSeeder as LaravelSkillSeeder;
use Illuminate\Database\Seeder;

/**
 * @deprecated Use Database\Seeders\SkillSeeder via `php artisan db:seed`.
 */
class SkillSeeder extends Seeder
{
    public function run()
    {
        $this->call(LaravelSkillSeeder::class);
    }
}
