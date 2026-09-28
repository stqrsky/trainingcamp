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
use Illuminate\Support\Str;
use Tests\TestCase;

class CalendarDeadlinesTest extends TestCase
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
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);

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

    public function test_month_view_shows_open_deadlines_of_own_team_only()
    {
        $this->task(['title' => 'Visible deadline', 'due_date' => '2026-09-15']);
        $this->task(['title' => 'Finished deadline', 'due_date' => '2026-09-16', 'status' => 'done']);
        Task::factory()->create(['title' => 'Foreign deadline', 'due_date' => '2026-09-17']);

        $response = $this->get(route('schedules.month', ['month' => '2026-09']));

        $response->assertOk()
            ->assertSee('Visible deadline')
            ->assertDontSee('Finished deadline')
            ->assertDontSee('Foreign deadline');
        $this->assertEquals(['2026-09-15'], $response->viewData('tasksByDate')->keys()->all());
    }

    public function test_month_cell_caps_items_and_links_to_more()
    {
        Schedule::factory()->count(2)->create(['team_id' => $this->team->id, 'date' => '2026-09-10']);
        $this->task(['title' => 'Deadline A', 'due_date' => '2026-09-10', 'priority' => 'urgent']);
        $this->task(['title' => 'Deadline B', 'due_date' => '2026-09-10', 'priority' => 'low']);

        $response = $this->get(route('schedules.month', ['month' => '2026-09']));

        // Only the calendar grid counts; the header bell may list the same tasks as reminders
        $grid = Str::after($response->getContent(), 'class="tc-month-grid"');
        $this->assertStringContainsString('Deadline A', $grid);
        $this->assertStringNotContainsString('Deadline B', $grid);
        $this->assertStringContainsString('+1 more', $grid);
    }

    public function test_week_view_has_deadline_row()
    {
        $this->task(['title' => 'Friday deadline', 'due_date' => '2026-10-02']);
        $this->task(['title' => 'Next week deadline', 'due_date' => '2026-10-06']);

        $response = $this->get(route('schedules.week', ['week' => '2026-09-30']));

        $response->assertOk()->assertSee('Friday deadline')->assertDontSee('Next week deadline');
    }

    public function test_day_view_lists_deadlines_of_that_day()
    {
        $this->task(['title' => 'Due on the 30th', 'due_date' => '2026-09-30']);
        $this->task(['title' => 'Due tomorrow', 'due_date' => '2026-10-01']);

        $response = $this->get(route('schedules.day', ['date' => '30/09/2026']));

        $response->assertOk()->assertSee('Due this day')->assertSee('Due on the 30th')->assertDontSee('Due tomorrow');
    }

    public function test_agenda_merges_sparrings_and_deadlines_in_time_order()
    {
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Evening sparring', 'date' => '2026-10-01', 'start' => '18:00', 'end' => '19:00']);
        $this->task(['title' => 'Morning deadline', 'due_date' => '2026-10-01', 'due_time' => '09:00']);
        $this->task(['title' => 'Untimed deadline', 'due_date' => '2026-10-01']);
        $this->task(['title' => 'Beyond range', 'due_date' => '2026-10-20']);

        $response = $this->get(route('schedules.agenda'));

        $response->assertOk()->assertDontSee('Beyond range');
        $titles = $response->viewData('days')['2026-10-01']->map(fn ($entry) => $entry['item']->title)->all();
        $this->assertSame(['Untimed deadline', 'Morning deadline', 'Evening sparring'], $titles);
    }

    public function test_agenda_starting_today_lists_overdue_tasks_first()
    {
        $this->task(['title' => 'Late task', 'due_date' => '2026-09-25']);

        $this->get(route('schedules.agenda'))->assertSeeInOrder(['Overdue', 'Late task']);

        $later = $this->get(route('schedules.agenda', ['from' => '2026-10-14']));
        $this->assertTrue($later->viewData('overdue')->isEmpty());
    }

    public function test_agenda_ignores_invalid_start_date()
    {
        $this->get(route('schedules.agenda', ['from' => 'garbage']))->assertOk();
    }
}
