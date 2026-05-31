<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $skillIds = Skill::pluck('id')->all();

        User::factory()->count(15)->create()->each(function ($user) use ($skillIds) {
            $user->roles()->attach(rand(1, 3));

            if ($skillIds === []) {
                return;
            }

            $startSkill = $skillIds[array_rand($skillIds)];

            foreach ($skillIds as $skillId) {
                if ($skillId >= $startSkill) {
                    $user->skills()->syncWithoutDetaching([$skillId]);
                }
            }
        });
    }
}
