<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->roles()->attach(Role::where('title', 'coach')->first());

        $this->actingAs($this->user);
    }

    public function test_user_can_view_tasks_index()
    {
        Task::factory()->count(3)->create(['team_id' => $this->team->id]);
        
        $response = $this->get(route('tasks.index'));
        $response->assertStatus(200);
        $response->assertViewHas(['overdue', 'today', 'upcoming', 'noDate', 'done']);
    }

    public function test_user_can_create_task()
    {
        $response = $this->post(route('tasks.store'), [
            'title' => 'New Test Task',
            'due_date' => now()->format('d/m/Y'),
            'priority' => 1,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'team_id' => $this->team->id,
            'priority' => 1
        ]);
    }

    public function test_user_can_toggle_task()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'status' => 0]);
        
        $response = $this->post(route('tasks.toggle', $task));
        
        $response->assertRedirect();
        $this->assertEquals(1, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);

        $this->post(route('tasks.toggle', $task));
        $this->assertEquals(0, $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_user_cannot_access_other_team_task()
    {
        $otherTeam = Team::factory()->create();
        $otherTask = Task::factory()->create(['team_id' => $otherTeam->id]);

        $response = $this->post(route('tasks.toggle', $otherTask));
        $response->assertStatus(403);
    }
}
