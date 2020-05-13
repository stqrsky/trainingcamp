<?php

use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Skill::firstOrCreate([
            'name' => 'Basic',
            'status' => 1
        ]);
        Skill::firstOrCreate([
            'name' => 'Intermediate',
            'status' => 1
        ]);
        Skill::firstOrCreate([
            'name' => 'Advance',
            'status' => 1
        ]);
        Skill::firstOrCreate([
            'name' => 'Expert',
            'status' => 1
        ]);
    }
}
