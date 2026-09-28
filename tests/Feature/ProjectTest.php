<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $max;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 14:00:00');
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->max = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $this->team->athletes()->attach($this->max);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function project(array $attributes = []): Project
    {
        return Project::factory()->create(array_merge(['team_id' => $this->team->id], $attributes));
    }

    public function test_project_can_be_created_with_owner_and_deadline()
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Summer camp',
            'status' => 'planning',
            'owner_id' => $this->max->id,
            'deadline' => '15/07/2027',
            'description' => 'Prepare the camp',
        ]);

        $project = Project::where('name', 'Summer camp')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame($this->team->id, $project->team_id);
        $this->assertSame($this->max->id, $project->owner_id);
        $this->assertSame('2027-07-15', Carbon::parse($project->deadline)->toDateString());
        $this->assertSame('created project “Summer camp”', Activity::where('action', 'project.created')->value('description'));
    }

    public function test_invalid_input_and_foreign_owner_are_rejected()
    {
        $stranger = User::factory()->member()->create();

        $this->post(route('projects.store'), [
            'name' => '',
            'status' => 'archived',
            'owner_id' => $stranger->id,
            'deadline' => '2027-07-15',
        ])->assertSessionHasErrors(['name', 'status', 'owner_id', 'deadline']);

        $this->assertSame(0, Project::count());
    }

    public function test_progress_and_members_are_derived_from_tasks()
    {
        $project = $this->project(['owner_id' => $this->user->id]);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id, 'status' => 'done']);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id, 'status' => 'done']);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id, 'assignee_id' => $this->max->id]);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id]);

        $this->assertSame(50, $project->progress);
        $this->assertEqualsCanonicalizing([$this->user->id, $this->max->id], $project->members->pluck('id')->all());
        $this->assertSame(0, $this->project()->progress, 'A project without tasks is at 0%');
    }

    public function test_detail_page_lists_open_and_done_tasks()
    {
        $project = $this->project(['name' => 'Fight night']);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id, 'title' => 'Book the hall']);
        Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id, 'title' => 'Print posters', 'status' => 'done']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Unrelated task']);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Fight night')
            ->assertSee('Book the hall')
            ->assertSee('Print posters')
            ->assertSee('50% done')
            ->assertDontSee('Unrelated task');
    }

    public function test_projects_of_other_teams_are_not_reachable()
    {
        $foreign = Project::factory()->create(['name' => 'Foreign project']);

        $this->get(route('projects.show', $foreign))->assertNotFound();
        $this->put(route('projects.update', $foreign), ['name' => 'Hijacked', 'status' => 'active'])->assertNotFound();
        $this->delete(route('projects.destroy', $foreign))->assertNotFound();
        $this->get(route('projects.index'))->assertOk()->assertDontSee('Foreign project');
        $this->assertDatabaseHas('projects', ['id' => $foreign->id, 'name' => 'Foreign project']);
    }

    public function test_tasks_can_be_linked_to_own_projects_only()
    {
        $project = $this->project();
        $foreign = Project::factory()->create();

        $this->get(route('tasks.create', ['project' => $project->id]))->assertOk()
            ->assertViewHas('preselectedProject', $project->id);
        $this->post(route('tasks.store'), ['title' => 'Linked', 'project_id' => $project->id])->assertRedirect();
        $this->post(route('tasks.store'), ['title' => 'Foreign link', 'project_id' => $foreign->id])
            ->assertSessionHasErrors('project_id');

        $this->assertSame($project->id, Task::where('title', 'Linked')->value('project_id'));
        $this->assertDatabaseMissing('tasks', ['title' => 'Foreign link']);
    }

    public function test_deleting_a_project_keeps_its_tasks()
    {
        $project = $this->project(['name' => 'Short lived']);
        $task = Task::factory()->create(['team_id' => $this->team->id, 'project_id' => $project->id]);

        $this->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertNull($task->fresh()->project_id);
    }

    public function test_completing_a_project_is_recorded_and_it_leaves_the_dashboard()
    {
        $project = $this->project(['name' => 'Almost done', 'deadline' => '2026-10-10']);
        $this->get(route('home'))->assertSee('Project progress')->assertSee('Almost done');

        $this->put(route('projects.update', $project), ['name' => 'Almost done', 'status' => 'completed']);

        $this->assertSame('completed project “Almost done”', Activity::where('action', 'project.status')->value('description'));
        $this->assertTrue($this->get(route('home'))->viewData('projects')->isEmpty());
        $this->get(route('projects.index'))->assertSee('Completed (1)');
    }

    public function test_projects_are_searchable()
    {
        $this->project(['name' => 'Regional championship']);

        $groups = collect($this->getJson(route('search', ['q' => 'championship']))->json('groups'));

        $this->assertSame(['Regional championship'], array_column($groups->firstWhere('label', 'Projects')['items'], 'title'));
    }
}
