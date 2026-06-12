<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'title'   => $this->faker->sentence(),
            'notes'   => $this->faker->paragraph(),
            'status'  => 0,
            'priority' => 0,
        ];
    }
}
