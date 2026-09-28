<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name'    => $this->faker->words(3, true),
            'status'  => 'active',
        ];
    }
}
