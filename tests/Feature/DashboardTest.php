<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-09-30 14:00:00'); // Wednesday

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->team = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha']);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function task(array $attributes): Task
    {
        return Task::factory()->create(array_merge(['team_id' => $this->team->id], $attributes));
    }

    public function test_stats_count_open_overdue_mine_and_weekly_sparrings()
    {
        $this->task(['due_date' => '2026-09-28']);                                  // overdue
        $this->task(['due_date' => '2026-09-30', 'assignee_id' => $this->user->id]); // today, mine
        $this->task(['due_date' => '2026-10-10']);                                  // upcoming
        $this->task(['status' => 'done', 'due_date' => '2026-09-01']);              // done: ignored
        Task::factory()->create(['due_date' => '2026-09-01']);                      // other team: ignored
        Schedule::factory()->create(['team_id' => $this->team->id, 'date' => '2026-10-02']); // this week
        Schedule::factory()->create(['team_id' => $this->team->id, 'date' => '2026-10-08']); // next week

        $stats = $this->get(route('home'))->assertOk()->viewData('stats');

        $this->assertSame(['open' => 3, 'overdue' => 1, 'mine' => 1, 'sparrings_this_week' => 1], $stats);
    }

    public function test_focus_list_shows_overdue_and_today_but_not_later_or_done()
    {
        $this->task(['title' => 'Overdue task', 'due_date' => '2026-09-29']);
        $this->task(['title' => 'Today task', 'due_date' => '2026-09-30']);
        $this->task(['title' => 'Later task', 'due_date' => '2026-10-05']);
        $this->task(['title' => 'Finished task', 'due_date' => '2026-09-30', 'status' => 'done']);

        $focus = $this->get(route('home'))->viewData('focusTasks')->pluck('title')->all();

        $this->assertSame(['Overdue task', 'Today task'], $focus);
    }

    public function test_upcoming_sparrings_skip_past_sessions_and_are_ordered()
    {
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'This morning', 'date' => '2026-09-30', 'start' => '09:00', 'end' => '10:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'This evening', 'date' => '2026-09-30', 'start' => '18:00', 'end' => '19:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Friday', 'date' => '2026-10-02', 'start' => '17:00', 'end' => '18:00']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Yesterday', 'date' => '2026-09-29']);

        $response = $this->get(route('home'));

        $this->assertSame(['This evening', 'Friday'], $response->viewData('upcomingSparrings')->pluck('title')->all());
        $response->assertSee('This evening')->assertDontSee('This morning');
    }

    public function test_team_overview_only_appears_with_several_teams()
    {
        $this->assertTrue($this->get(route('home'))->viewData('teamOverview')->isEmpty());

        $bravo = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Bravo']);
        Task::factory()->create(['team_id' => $bravo->id, 'due_date' => '2026-09-01']);
        Task::factory()->create(['team_id' => $bravo->id]);
        Task::factory()->create(['team_id' => Team::factory()->create(['name' => 'Foreign'])->id]);

        $overview = $this->get(route('home'))->assertSee('Your teams')->viewData('teamOverview');

        $this->assertSame(['Alpha', 'Bravo'], $overview->pluck('name')->all());
        $this->assertSame([0, 2], $overview->pluck('open_count')->all());
        $this->assertSame([0, 1], $overview->pluck('overdue_count')->all());
    }

    public function test_user_without_team_sees_setup_prompt()
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))->assertOk()->assertSee('Set up your team')->assertDontSee('Open tasks');
    }
}
