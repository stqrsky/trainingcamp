<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SparringWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $athletes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-09-30 14:00:00');

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->athletes = User::factory()->member()->count(2)->create();
        $this->team->athletes()->attach($this->athletes);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'date'           => '02/10/2026',
            'start'          => '18:00',
            'end'            => '19:30',
            'first_athlete'  => $this->athletes[0]->id,
            'second_athlete' => $this->athletes[1]->id,
        ], $overrides);
    }

    public function test_new_sparring_defaults_to_planned_and_stores_goal()
    {
        $this->post(route('schedules.store'), $this->payload(['title' => 'Goal session', 'goal' => 'Improve the jab']))
            ->assertRedirect(route('schedules.index', ['date' => '02/10/2026']));

        $this->assertDatabaseHas('schedules', ['title' => 'Goal session', 'status' => 'planned', 'goal' => 'Improve the jab']);
    }

    public function test_update_stores_status_and_result()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Done soon']);

        $this->put(route('schedules.update', $schedule), $this->payload([
            'title' => 'Done soon',
            'status' => 'completed',
            'result' => 'Great defence, work on stamina',
        ]))->assertRedirect();

        $schedule->refresh();
        $this->assertEquals('completed', $schedule->status);
        $this->assertEquals('Great defence, work on stamina', $schedule->result);
    }

    public function test_update_without_status_keeps_current_status()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id, 'status' => 'confirmed']);

        $this->put(route('schedules.update', $schedule), $this->payload())->assertRedirect();

        $this->assertEquals('confirmed', $schedule->fresh()->status);
    }

    public function test_status_can_be_changed_from_the_list()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id]);

        $this->from(route('schedules.index'))
            ->patch(route('schedules.status', $schedule), ['status' => 'cancelled'])
            ->assertRedirect(route('schedules.index'));

        $this->assertTrue($schedule->fresh()->isCancelled());
    }

    public function test_unknown_status_is_rejected()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id]);

        $this->patch(route('schedules.status', $schedule), ['status' => 'postponed'])->assertSessionHasErrors('status');
        $this->post(route('schedules.store'), $this->payload(['status' => 'postponed']))->assertSessionHasErrors('status');

        $this->assertEquals('planned', $schedule->fresh()->status);
    }

    public function test_status_of_foreign_sparring_cannot_be_changed()
    {
        $schedule = Schedule::factory()->create(['team_id' => Team::factory()->create()->id]);

        $this->patch(route('schedules.status', $schedule), ['status' => 'cancelled'])->assertNotFound();

        $this->assertEquals('planned', $schedule->fresh()->status);
    }

    public function test_list_shows_status_goal_and_result()
    {
        Schedule::factory()->create([
            'team_id' => $this->team->id,
            'date' => '2026-09-30',
            'status' => 'completed',
            'goal' => 'Keep the guard up',
            'result' => 'Guard stayed up',
        ]);

        $this->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Keep the guard up')
            ->assertSee('Guard stayed up');
    }

    public function test_cancelled_sparrings_leave_the_dashboard_but_stay_in_the_calendar()
    {
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Called off', 'date' => '2026-10-01', 'status' => 'cancelled']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Still on', 'date' => '2026-10-01']);

        $home = $this->get(route('home'));
        $this->assertSame(['Still on'], $home->viewData('upcomingSparrings')->pluck('title')->all());
        $this->assertSame(1, $home->viewData('stats')['sparrings_this_week']);

        $this->get(route('schedules.month', ['month' => '2026-10']))->assertSee('Called off');
    }
}
