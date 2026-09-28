<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use App\Services\SparringMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SparringMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $boxing;
    protected $clinch;
    protected $anna;   // target athlete
    protected $ben;    // best match
    protected $carl;   // poor match
    protected $dana;   // decent match, but sparred with Anna twice recently

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 14:00:00'); // Wednesday

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->team->coaches()->attach($this->user);
        $this->boxing = $this->team->skills()->create(['name' => 'Boxing']);
        $this->clinch = $this->team->skills()->create(['name' => 'Clinch']);

        $this->anna = $this->athlete('Anna', 'intermediate', 70, ['Boxing' => 'advanced', 'Clinch' => 'beginner'], [[2, '18:00', '20:00']]);
        $this->ben = $this->athlete('Ben', 'intermediate', 72, ['Boxing' => 'expert'], [[2, '19:00', '21:00']]);
        $this->carl = $this->athlete('Carl', 'expert', 90, ['Boxing' => 'beginner'], []);
        $this->dana = $this->athlete('Dana', 'advanced', 78, ['Boxing' => 'advanced', 'Clinch' => 'beginner'], [[2, '18:30', '19:00']]);
        foreach (['2026-09-15', '2026-09-22'] as $date) {
            Schedule::factory()->create(['team_id' => $this->team->id, 'date' => $date, 'status' => 'completed'])
                ->participants()->attach([$this->anna->id, $this->dana->id]);
        }

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function athlete(string $name, ?string $level, ?int $weight, array $skills, array $slots, bool $active = true, ?Team $team = null): User
    {
        $team ??= $this->team;
        $user = User::factory()->member()->create(['first_name' => $name, 'last_name' => 'Test']);
        $user->userDetail()->create(['experience_level' => $level, 'weight' => $weight]);
        foreach ($skills as $skillName => $skillLevel) {
            $user->skills()->attach($team->skills()->where('name', $skillName)->value('id'), ['level' => $skillLevel]);
        }
        foreach ($slots as [$weekday, $start, $end]) {
            $user->availabilities()->create(['weekday' => $weekday, 'start' => $start, 'end' => $end]);
        }
        $team->athletes()->attach($user, ['active' => $active]);
        return $user;
    }

    private function suggestions(User $athlete)
    {
        return app(SparringMatcher::class)->suggestionsFor($athlete, $this->team);
    }

    public function test_partners_are_ranked_by_explainable_points()
    {
        $matches = $this->suggestions($this->anna);

        $this->assertSame(['Ben', 'Dana', 'Carl'], $matches->pluck('partner.first_name')->all());
        $this->assertSame([9, 4, -1], $matches->pluck('points')->all());

        $ben = collect($matches[0]['reasons'])->pluck('points', 'label')->all();
        $this->assertSame([
            'Same level (Intermediate)' => 3,
            'Shared skills: Boxing' => 1,
            'Both free Tue 19:00–20:00' => 3,
            '2 kg weight difference' => 2,
        ], $ben);
        $this->assertSame(9, array_sum($ben));
    }

    public function test_recent_pairings_cost_a_point_for_variety()
    {
        $dana = collect($this->suggestions($this->anna)[1]['reasons'])->pluck('points', 'label')->all();

        $this->assertSame(-1, $dana['Sparred together 2× in the last 30 days']);
        $this->assertSame(2, $dana['Shared skills: Boxing, Clinch']);
        $this->assertSame(0, $dana['No common time slot'], 'A 30-minute overlap is too short');
    }

    public function test_common_slot_suggests_the_next_date()
    {
        $slot = $this->suggestions($this->anna)[0]['slot'];

        $this->assertSame(2, $slot['weekday']);
        $this->assertSame('2026-10-06', $slot['date']->toDateString());
    }

    public function test_inactive_athletes_coaches_and_other_teams_are_never_suggested()
    {
        $this->athlete('Inactive', 'intermediate', 70, [], [[2, '18:00', '20:00']], false);
        $otherTeam = Team::factory()->create();
        $this->athlete('Foreign', 'intermediate', 70, [], [[2, '18:00', '20:00']], true, $otherTeam);

        $names = $this->suggestions($this->anna)->pluck('partner.first_name')->all();

        $this->assertNotContains('Inactive', $names);
        $this->assertNotContains('Foreign', $names);
        $this->assertNotContains('Anna', $names);
        $this->assertNotContains($this->user->first_name, $names);
    }

    public function test_missing_data_is_named_instead_of_guessed()
    {
        $newbie = $this->athlete('Newbie', null, null, [], []);

        $reasons = collect($this->suggestions($newbie)->firstWhere('partner.first_name', 'Ben')['reasons'])
            ->pluck('points', 'label')->all();

        $this->assertSame(1, $reasons['Experience level not set']);
        $this->assertSame(0, $reasons['Weight unknown']);
    }

    public function test_partner_endpoint_returns_suggestions_for_own_active_athletes_only()
    {
        $json = $this->getJson(route('schedules.partners', ['athlete' => $this->anna->id]))->assertOk()->json('suggestions');
        $this->assertSame('Ben Test', $json[0]['name']);
        $this->assertSame('06/10/2026', $json[0]['slot']['date']);

        $foreign = $this->athlete('Foreign', 'intermediate', 70, [], [], true, Team::factory()->create());
        $this->getJson(route('schedules.partners', ['athlete' => $foreign->id]))->assertExactJson(['suggestions' => []]);
    }

    public function test_detail_page_shows_suggestions_for_athletes_only()
    {
        $this->get(route('user.athletes.detail', ['id' => $this->anna->id]))
            ->assertOk()
            ->assertSee('Suggested sparring partners')
            ->assertSee('Ben Test')
            ->assertSee('9<small>/11</small>', false);

        $this->get(route('user.athletes.detail', ['id' => $this->user->id]))
            ->assertOk()
            ->assertDontSee('Suggested sparring partners');
    }

    public function test_plan_sparring_link_prefills_the_form()
    {
        $response = $this->get(route('schedules.create', [
            'athlete' => $this->anna->id,
            'partner' => $this->ben->id,
            'date' => '06/10/2026',
            'start' => '19:00',
            'end' => 'not-a-time',
        ]));

        $response->assertOk();
        $this->assertSame($this->anna->id, $response->viewData('first_athlete'));
        $this->assertSame($this->ben->id, $response->viewData('second_athlete'));
        $this->assertSame(['date' => '06/10/2026', 'start' => '19:00', 'end' => null], $response->viewData('prefill'));
    }

    public function test_skill_matrix_lists_active_members_against_team_skills()
    {
        $this->athlete('Inactive', 'expert', 70, ['Boxing' => 'expert'], [], false);

        $response = $this->get(route('user.athletes.matrix'))->assertOk();

        $response->assertSee('Skill Matrix')->assertSee('Anna Test')->assertSee('Clinch')->assertDontSee('Inactive Test');
        $this->assertSame(
            ['Anna', 'Ben', 'Carl', 'Dana', $this->user->first_name],
            $response->viewData('members')->pluck('member.first_name')->all()
        );
    }
}
