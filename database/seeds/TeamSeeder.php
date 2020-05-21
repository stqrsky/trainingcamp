<?php

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\User;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $first_team = Team::firstOrCreate([
            'name' => 'Legendary Team',
            'status' => 1,
            'description' => '-'
        ]);
        $first_team->coaches()->attach(User::whereHas('roles', function ($role) {
            $role->where('title', 'coach');
        })->orderBy('first_name', 'ASC')->take(2)->get());


        $second_team = Team::firstOrCreate([
            'name' => 'Rare Team',
            'status' => 1,
            'description' => '-'
        ]);
        $second_team->coaches()->attach(User::whereHas('roles', function ($role) {
            $role->where('title', 'coach');
        })->orderBy('first_name', 'DESC')->take(2)->get());
    }
}
