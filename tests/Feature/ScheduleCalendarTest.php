<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
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

    public function test_month_view_works()
    {
        $response = $this->get(route('schedules.month'));
        $response->assertStatus(200);
        $response->assertViewIs('frontend.schedules.month');
    }

    public function test_week_view_works()
    {
        $response = $this->get(route('schedules.week'));
        $response->assertStatus(200);
        $response->assertViewIs('frontend.schedules.week');
    }

    public function test_day_view_works()
    {
        $response = $this->get(route('schedules.day'));
        $response->assertStatus(200);
        $response->assertViewIs('frontend.schedules.day');
    }

    public function test_planner_view_works()
    {
        $response = $this->get(route('schedules.planner'));
        $response->assertStatus(200);
        $response->assertViewIs('frontend.schedules.planner');
    }

    public function test_sparring_on_first_day_of_month_is_shown_in_month_view()
    {
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'First of October', 'date' => '2026-10-01']);

        $this->get(route('schedules.month', ['month' => '2026-10']))->assertSee('First of October');
    }

    public function test_sparring_on_monday_is_shown_in_week_view()
    {
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Monday session', 'date' => '2026-09-28']);

        $this->get(route('schedules.week', ['week' => '2026-09-30']))->assertSee('Monday session');
    }
}
