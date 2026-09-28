<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $athlete;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->athlete = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $this->team->athletes()->attach($this->athlete);

        $this->actingAs($this->user);
    }

    public function test_task_can_be_created_with_status_priority_and_assignee()
    {
        $response = $this->post(route('tasks.store'), [
            'title' => 'Prepare sparring plan',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'assignee_id' => $this->athlete->id,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Prepare sparring plan',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'assignee_id' => $this->athlete->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_new_task_defaults_to_todo_and_medium()
    {
        $this->post(route('tasks.store'), ['title' => 'Plain task']);

        $this->assertDatabaseHas('tasks', ['title' => 'Plain task', 'status' => 'todo', 'priority' => 'medium']);
    }

    public function test_task_cannot_be_assigned_outside_the_team()
    {
        $stranger = User::factory()->member()->create();

        $response = $this->post(route('tasks.store'), [
            'title' => 'Foreign assignee',
            'assignee_id' => $stranger->id,
        ]);

        $response->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseMissing('tasks', ['title' => 'Foreign assignee']);
    }

    public function test_invalid_status_and_priority_are_rejected()
    {
        $response = $this->post(route('tasks.store'), [
            'title' => 'Bad values',
            'status' => 'archived',
            'priority' => 'critical',
        ]);

        $response->assertSessionHasErrors(['status', 'priority']);
    }

    public function test_board_groups_tasks_by_status()
    {
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Backlog idea', 'status' => 'backlog']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Under review', 'status' => 'review']);

        $response = $this->get(route('tasks.index', ['view' => 'board']));

        $response->assertOk()->assertViewIs('frontend.tasks.board');
        $columns = $response->viewData('columns');
        $this->assertSame(array_keys(Task::STATUSES), $columns->keys()->all());
        $this->assertEquals(['Backlog idea'], $columns['backlog']->pluck('title')->all());
        $this->assertEquals(['Under review'], $columns['review']->pluck('title')->all());
        $this->assertCount(0, $columns['todo']);
    }

    public function test_moving_a_task_persists_status_and_completion()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'status' => 'review']);

        $this->patchJson(route('tasks.move', $task), ['status' => 'done'])
            ->assertOk()
            ->assertJson(['status' => 'done', 'status_label' => 'Done']);
        $this->assertEquals('done', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);

        $this->patchJson(route('tasks.move', $task), ['status' => 'in_progress'])->assertOk();
        $this->assertEquals('in_progress', $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_status_select_without_javascript_redirects_back()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'status' => 'todo']);

        $this->from(route('tasks.index', ['view' => 'board']))
            ->patch(route('tasks.move', $task), ['status' => 'backlog'])
            ->assertRedirect(route('tasks.index', ['view' => 'board']));

        $this->assertEquals('backlog', $task->fresh()->status);
    }

    public function test_moving_to_unknown_status_is_rejected()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'status' => 'todo']);

        $this->patchJson(route('tasks.move', $task), ['status' => 'archived'])->assertStatus(422);
        $this->assertEquals('todo', $task->fresh()->status);
    }

    public function test_cannot_move_task_of_another_team()
    {
        $task = Task::factory()->create(['team_id' => Team::factory()->create()->id, 'status' => 'todo']);

        $this->patchJson(route('tasks.move', $task), ['status' => 'done'])->assertForbidden();
        $this->assertEquals('todo', $task->fresh()->status);
    }

    public function test_my_tasks_shows_only_tasks_assigned_to_me()
    {
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Mine', 'assignee_id' => $this->user->id]);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'For Max', 'assignee_id' => $this->athlete->id]);

        $response = $this->get(route('tasks.index', ['mine' => 1]));

        $response->assertOk()->assertSee('Mine')->assertDontSee('For Max');
    }

    public function test_edit_form_updates_task_and_forms_are_not_nested()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Old title', 'status' => 'todo']);

        $html = $this->get(route('tasks.edit', $task))->assertOk()->getContent();
        $depth = 0;
        preg_match_all('/<form\b|<\/form>/', $html, $tags);
        foreach ($tags[0] as $tag) {
            $depth += $tag === '<form' ? 1 : -1;
            $this->assertLessThanOrEqual(1, $depth, 'Nested <form> elements break the edit page buttons');
        }

        $this->put(route('tasks.update', $task), [
            'title' => 'New title',
            'status' => 'review',
            'priority' => 'low',
            'assignee_id' => $this->athlete->id,
        ])->assertRedirect(route('tasks.index'));

        $task->refresh();
        $this->assertEquals('New title', $task->title);
        $this->assertEquals('review', $task->status);
        $this->assertEquals('low', $task->priority);
        $this->assertEquals($this->athlete->id, $task->assignee_id);
    }

    public function test_open_tasks_are_ordered_by_priority()
    {
        foreach (['low', 'urgent', 'medium', 'high'] as $priority) {
            Task::factory()->create(['team_id' => $this->team->id, 'title' => "P-$priority", 'priority' => $priority]);
        }

        $titles = Task::where('team_id', $this->team->id)->open()->orderByPriority()->pluck('title')->all();

        $this->assertEquals(['P-urgent', 'P-high', 'P-medium', 'P-low'], $titles);
    }
}
