<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Reminders;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-09-30 14:00:00');

        $this->user = User::factory()->create(['first_name' => 'Owner']);
        $this->actingAs($this->user);
        $this->post(route('teams.store'), ['name' => 'Alpha']);
        $this->team = Team::where('name', 'Alpha')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function feed(): array
    {
        return Activity::where('team_id', $this->team->id)->orderBy('id')->pluck('description')->all();
    }

    public function test_team_creation_is_recorded_without_owner_membership_noise()
    {
        $this->assertSame(['created the team “Alpha”'], $this->feed());
        $this->assertSame($this->user->id, Activity::first()->user_id);
    }

    public function test_relevant_task_changes_are_recorded_and_plain_edits_are_not()
    {
        $this->post(route('tasks.store'), ['title' => 'Wrap hands']);
        $task = Task::where('title', 'Wrap hands')->firstOrFail();

        $task->update(['notes' => 'Only a note']);
        $this->patchJson(route('tasks.move', $task), ['status' => 'review']);
        $this->patchJson(route('tasks.move', $task), ['status' => 'done']);
        $this->post(route('tasks.toggle', $task));
        $task->update(['due_date' => '2026-10-03', 'assignee_id' => $this->user->id]);
        $this->delete(route('tasks.destroy', $task));

        $this->assertSame([
            'created the team “Alpha”',
            'created task “Wrap hands”',
            'moved task “Wrap hands” to Review',
            'completed task “Wrap hands”',
            'reopened task “Wrap hands”',
            'assigned task “Wrap hands” to Owner ' . $this->user->last_name,
            'changed the deadline of “Wrap hands” to 3 Oct',
            'deleted task “Wrap hands”',
        ], $this->feed());
    }

    public function test_sparring_posts_and_memberships_are_recorded()
    {
        $max = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $this->team->athletes()->attach($max);
        $this->team->athletes()->updateExistingPivot($max->id, ['active' => false]);
        $sparring = Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Friday', 'date' => '2026-10-02', 'start' => '18:00']);
        $sparring->update(['status' => 'confirmed']);
        $sparring->update(['date' => '2026-10-03']);
        Notification::create(['user_id' => $this->user->id, 'team_id' => $this->team->id, 'title' => 'Gym closed', 'description' => 'x']);
        $this->team->athletes()->detach($max);

        $this->assertSame([
            'created the team “Alpha”',
            'added Max Muster as athlete',
            'set Max Muster inactive',
            'planned sparring “Friday” for Fri 2 Oct 18:00',
            'confirmed sparring “Friday”',
            'rescheduled sparring “Friday” to Sat 3 Oct 18:00',
            'posted the announcement “Gym closed”',
            'removed Max Muster from the team',
        ], $this->feed());
    }

    public function test_activity_page_shows_own_team_only_and_names_the_viewer_you()
    {
        $this->post(route('tasks.store'), ['title' => 'Visible task']);
        Activity::create(['team_id' => Team::factory()->create()->id, 'action' => 'task.created', 'description' => 'created task “Secret”']);

        $this->get(route('activity'))
            ->assertOk()
            ->assertSee('Visible task')
            ->assertDontSee('Secret')
            ->assertSee('<strong>You</strong>', false);
        $this->get(route('home'))->assertSee('Recent activity')->assertSee('Visible task');
    }

    public function test_reminders_cover_overdue_today_and_next_24_hours()
    {
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Late', 'due_date' => '2026-09-28']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Today', 'due_date' => '2026-09-30']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Done late', 'due_date' => '2026-09-28', 'status' => 'done']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Next week', 'due_date' => '2026-10-07']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Tonight', 'date' => '2026-09-30', 'start' => '18:00', 'end' => '19:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Tomorrow noon', 'date' => '2026-10-01', 'start' => '12:00', 'end' => '13:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Tomorrow evening', 'date' => '2026-10-01', 'start' => '18:00', 'end' => '19:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Called off', 'date' => '2026-09-30', 'start' => '20:00', 'end' => '21:00', 'status' => 'cancelled']);

        $reminders = app(Reminders::class)->for($this->user, $this->team);

        $this->assertSame(4, $reminders['count']);
        $this->assertSame(['Late', 'Today', 'Tonight', 'Tomorrow noon'], $reminders['items']->pluck('title')->all());
        $this->get(route('home'))->assertSee('aria-label="Reminders: 4"', false);
    }

    public function test_reminder_types_can_be_switched_off()
    {
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Late', 'due_date' => '2026-09-28']);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Today', 'due_date' => '2026-09-30']);

        $this->put(route('user.notifications.put'), ['reminders' => ['due_today' => 1]])
            ->assertRedirect(route('user.notifications'));

        $user = $this->user->fresh();
        $this->assertFalse($user->wantsReminder('overdue_tasks'));
        $this->assertTrue($user->wantsReminder('due_today'));
        $this->assertSame(['Today'], app(Reminders::class)->for($user, $this->team)['items']->pluck('title')->all());
        $this->get(route('user.notifications'))->assertOk()->assertSee('Sparrings in the next 24 hours');
    }
}
