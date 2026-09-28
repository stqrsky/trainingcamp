<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $max;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha']);
        $this->max = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $this->max->userDetail()->create(['nick_name' => 'Hammer']);
        $this->team->athletes()->attach($this->max);

        $this->actingAs($this->user);
    }

    private function search(string $query): array
    {
        return collect($this->getJson(route('search', ['q' => $query]))->assertOk()->json('groups'))
            ->mapWithKeys(fn ($group) => [$group['label'] => array_column($group['items'], 'title')])
            ->all();
    }

    public function test_search_requires_login()
    {
        auth()->logout();

        $this->getJson(route('search', ['q' => 'max']))->assertUnauthorized();
    }

    public function test_short_or_too_long_queries_are_handled()
    {
        $this->assertSame([], $this->search('m'));
        $this->getJson(route('search', ['q' => str_repeat('x', 101)]))->assertStatus(422);
    }

    public function test_finds_members_tasks_sparrings_and_posts_of_the_active_team()
    {
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Hammer drills for Max']);
        $sparring = Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Friday session']);
        $sparring->participants()->attach($this->max);
        Notification::create(['user_id' => $this->user->id, 'team_id' => $this->team->id, 'title' => 'Max joins', 'description' => 'Welcome']);

        $results = $this->search('max');

        $this->assertSame(['Max Muster'], $results['Members']);
        $this->assertSame(['Hammer drills for Max'], $results['Tasks']);
        $this->assertSame(['Friday session'], $results['Sparrings']);
        $this->assertSame(['Max joins'], $results['Announcements']);
        $this->assertSame(['Max Muster'], $this->search('Hammer')['Members']);
    }

    public function test_results_never_leak_other_teams_or_accounts()
    {
        $otherTeam = Team::factory()->create();
        $stranger = User::factory()->member()->create(['first_name' => 'Maxine']);
        $otherTeam->athletes()->attach($stranger);
        Task::factory()->create(['team_id' => $otherTeam->id, 'title' => 'Max secret task']);
        Schedule::factory()->create(['team_id' => $otherTeam->id, 'title' => 'Max secret sparring']);
        Notification::create(['user_id' => $otherTeam->user_id, 'team_id' => $otherTeam->id, 'title' => 'Max secret post', 'description' => 'x']);

        $results = $this->search('max');

        $this->assertSame(['Members' => ['Max Muster']], $results);
    }

    public function test_palette_offers_commands_and_switching_to_other_own_teams()
    {
        Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Bravo']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="tc-palette"', false)
            ->assertSee('Plan sparring')
            ->assertSee('Switch to Bravo')
            ->assertDontSee('Switch to Alpha');
    }

    public function test_palette_command_data_is_escaped_inside_the_script_tag()
    {
        Team::factory()->create(['user_id' => $this->user->id, 'name' => '</script><script>alert(1)</script>']);

        $this->get(route('home'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
