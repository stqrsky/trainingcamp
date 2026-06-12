<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'title'   => $this->faker->sentence(),
            'date'    => now()->format('Y-m-d'),
            'start'   => '09:00',
            'end'     => '10:00',
            'color'   => 'blue',
            'status'  => 1,
        ];
    }
}
