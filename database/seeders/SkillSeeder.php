<?php

namespace Database\Seeders;

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
        // Skills are created per team on the "Edit team" page. The former Basic/Intermediate/
        // Advance/Expert entries were experience levels and now live in user_detail.experience_level.
    }
}
