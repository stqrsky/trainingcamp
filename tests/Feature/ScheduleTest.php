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

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $athletes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->athletes = User::factory()->count(2)->create();
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
            'date'           => now()->format('d/m/Y'),
            'start'          => '18:00',
            'end'            => '19:00',
            'first_athlete'  => $this->athletes[0]->id,
            'second_athlete' => $this->athletes[1]->id,
            'color'          => 'blue',
        ], $overrides);
    }

    public function test_coach_can_create_sparring_with_own_athletes()
    {
        $response = $this->post(route('schedules.store'), $this->payload(['title' => 'Friday sparring']));

        $response->assertRedirect(route('schedules.index', ['date' => now()->format('d/m/Y')]));
        $schedule = Schedule::where('title', 'Friday sparring')->firstOrFail();
        $this->assertEquals($this->team->id, $schedule->team_id);
        $this->assertCount(2, $schedule->participants);
    }

    public function test_athlete_from_other_team_cannot_be_added()
    {
        $otherTeam = Team::factory()->create();
        $stranger = User::factory()->create();
        $otherTeam->athletes()->attach($stranger);

        $response = $this->post(route('schedules.store'), $this->payload([
            'title' => 'Sneaky sparring',
            'second_athlete' => $stranger->id,
        ]));

        $response->assertSessionHasErrors('second_athlete');
        $this->assertDatabaseMissing('schedules', ['title' => 'Sneaky sparring']);
    }

    public function test_end_time_must_be_after_start_time()
    {
        $response = $this->post(route('schedules.store'), $this->payload([
            'start' => '19:00',
            'end'   => '18:00',
        ]));

        $response->assertSessionHasErrors('end');
    }

    public function test_unknown_color_is_rejected()
    {
        $response = $this->post(route('schedules.store'), $this->payload(['color' => 'red;background:url(x)']));

        $response->assertSessionHasErrors('color');
    }

    public function test_invalid_date_parameters_fall_back_to_default_view()
    {
        $this->get(route('schedules.index', ['date' => 'not-a-date']))->assertOk();
        $this->get(route('schedules.index', ['date' => ['array']]))->assertOk();
        $this->get(route('schedules.day', ['date' => '99/99/nope']))->assertOk();
        $this->get(route('schedules.planner', ['date' => 'garbage']))->assertOk();
        $this->get(route('schedules.week', ['week' => 'garbage']))->assertOk();
        $this->get(route('schedules.month', ['month' => 'garbage']))->assertOk();
    }

    public function test_month_parameter_does_not_overflow_into_next_month()
    {
        Carbon::setTestNow('2026-01-30 12:00:00');

        $response = $this->get(route('schedules.month', ['month' => '2026-02']));

        $response->assertOk();
        $this->assertEquals('2026-02-01', $response->viewData('date')->format('Y-m-d'));
    }

    public function test_user_without_team_gets_no_server_error_on_foreign_schedule()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id]);
        $this->actingAs(User::factory()->create());

        $this->get(route('schedules.edit', $schedule))->assertRedirect();
        $this->put(route('schedules.update', $schedule), $this->payload())->assertRedirect();
        $this->delete(route('schedules.destroy', $schedule))->assertRedirect();
        $this->post(route('schedules.store'), $this->payload())->assertRedirect(route('user.setting'));

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_edit_works_with_incomplete_participant_list()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id]);
        $schedule->participants()->attach($this->athletes[0]);

        $this->get(route('schedules.edit', $schedule))->assertOk();
    }
}
