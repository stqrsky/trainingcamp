<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $boxing;
    protected $clinch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-09-30 14:00:00');

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->team->coaches()->attach($this->user);
        $this->boxing = $this->team->skills()->create(['name' => 'Boxing']);
        $this->clinch = $this->team->skills()->create(['name' => 'Clinch']);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function memberPayload(array $overrides = []): array
    {
        return array_merge([
            'user_type' => 'athlete',
            'first_name' => 'Max',
            'last_name' => 'Muster',
            'nick_name' => 'Maxi',
            'date_of_birth' => '01/01/2000',
            'weight' => 72,
            'height' => 180,
            'availability_present' => 1,
        ], $overrides);
    }

    private function max(): User
    {
        return User::where('first_name', 'Max')->where('last_name', 'Muster')->firstOrFail();
    }

    public function test_member_is_created_with_level_skills_and_availability()
    {
        $foreignSkill = Team::factory()->create()->skills()->create(['name' => 'Foreign']);

        $this->post(route('user.athletes.post'), $this->memberPayload([
            'experience_level' => 'intermediate',
            'skill_levels' => [$this->boxing->id => 'advanced', $this->clinch->id => '', $foreignSkill->id => 'expert'],
            'availability' => [
                ['weekday' => 2, 'start' => '18:00', 'end' => '20:00'],
                ['weekday' => 4, 'start' => '19:00', 'end' => '21:00'],
            ],
        ]))->assertRedirect(route('user.athletes'));

        $max = $this->max();
        $this->assertEquals('intermediate', $max->userDetail->experience_level);
        $this->assertEquals(['Boxing' => 'advanced'], $max->skills->pluck('pivot.level', 'name')->all());
        $this->assertEquals(['Tue 18:00–20:00', 'Thu 19:00–21:00'], $max->availabilities->pluck('label')->all());
    }

    public function test_availability_end_must_follow_start_and_levels_must_be_known()
    {
        $response = $this->post(route('user.athletes.post'), $this->memberPayload([
            'experience_level' => 'grandmaster',
            'skill_levels' => [$this->boxing->id => 'legend'],
            'availability' => [['weekday' => 2, 'start' => '20:00', 'end' => '18:00']],
        ]));

        $response->assertSessionHasErrors(['experience_level', 'skill_levels.' . $this->boxing->id, 'availability.0.end']);
        $this->assertDatabaseMissing('users', ['first_name' => 'Max', 'last_name' => 'Muster']);
    }

    public function test_updating_a_member_replaces_availability_and_can_clear_it()
    {
        $this->post(route('user.athletes.post'), $this->memberPayload([
            'availability' => [['weekday' => 1, 'start' => '17:00', 'end' => '18:00']],
        ]));
        $max = $this->max();
        $update = $this->memberPayload(['skill_levels' => [$this->clinch->id => 'beginner']]);
        unset($update['user_type']);

        $this->put(route('user.athletes.update', ['id' => $max->id]), $update + [
            'availability' => [['weekday' => 6, 'start' => '10:00', 'end' => '12:00']],
        ])->assertRedirect(route('user.athletes'));
        $this->assertEquals(['Sat 10:00–12:00'], $max->fresh()->availabilities->pluck('label')->all());
        $this->assertEquals(['Clinch' => 'beginner'], $max->fresh()->skills->pluck('pivot.level', 'name')->all());

        $this->put(route('user.athletes.update', ['id' => $max->id]), $update)->assertRedirect();
        $this->assertCount(0, $max->fresh()->availabilities);
    }

    public function test_saving_in_one_team_keeps_skill_levels_of_another_team()
    {
        $otherTeam = Team::factory()->create(['user_id' => $this->user->id]);
        $grappling = $otherTeam->skills()->create(['name' => 'Grappling']);
        $this->user->skills()->attach($grappling, ['level' => 'expert']);

        $this->put(route('user.profile.setting.put'), [
            'first_name' => 'Owner',
            'last_name' => 'Account',
            'nick_name' => 'O',
            'date_of_birth' => '01/01/1990',
            'weight' => 80,
            'height' => 182,
            'experience_level' => 'expert',
            'skill_levels' => [$this->boxing->id => 'intermediate'],
            'availability_present' => 1,
        ])->assertRedirect(route('user.profile'));

        $levels = $this->user->fresh()->skills->pluck('pivot.level', 'name')->all();
        $this->assertEquals(['Boxing' => 'intermediate', 'Grappling' => 'expert'], collect($levels)->sortKeys()->all());
        $this->assertEquals('expert', $this->user->fresh()->userDetail->experience_level);
    }

    public function test_team_skill_catalog_can_be_extended_and_trimmed()
    {
        $this->post(route('teams.skills.store'), ['skill_name' => 'Conditioning'])->assertRedirect(route('teams.edit'));
        $this->post(route('teams.skills.store'), ['skill_name' => 'Boxing'])->assertSessionHasErrors('skill_name');
        $this->assertEquals(['Boxing', 'Clinch', 'Conditioning'], $this->team->skills()->pluck('name')->all());

        $member = User::factory()->member()->create();
        $member->skills()->attach($this->clinch, ['level' => 'beginner']);
        $this->delete(route('teams.skills.destroy', $this->clinch))->assertRedirect(route('teams.edit'));

        $this->assertDatabaseMissing('skills', ['id' => $this->clinch->id]);
        $this->assertCount(0, $member->fresh()->skills);
    }

    public function test_skills_of_other_teams_cannot_be_removed()
    {
        $foreignSkill = Team::factory()->create()->skills()->create(['name' => 'Foreign']);

        $this->delete(route('teams.skills.destroy', $foreignSkill))->assertNotFound();

        $this->assertDatabaseHas('skills', ['id' => $foreignSkill->id]);
    }

    public function test_member_detail_shows_levels_availability_and_scoped_stats()
    {
        $max = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $max->userDetail()->create(['experience_level' => 'advanced']);
        $max->skills()->attach($this->boxing, ['level' => 'expert']);
        $max->availabilities()->create(['weekday' => 3, 'start' => '18:00', 'end' => '19:30']);
        $this->team->athletes()->attach($max);
        Task::factory()->create(['team_id' => $this->team->id, 'assignee_id' => $max->id]);
        Task::factory()->create(['team_id' => $this->team->id, 'assignee_id' => $max->id, 'status' => 'done']);
        Task::factory()->create(['team_id' => Team::factory()->create()->id, 'assignee_id' => $max->id]);
        $next = Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Next bout', 'date' => '2026-10-02']);
        $next->participants()->attach($max);
        $past = Schedule::factory()->create(['team_id' => $this->team->id, 'date' => '2026-09-01', 'status' => 'completed']);
        $past->participants()->attach($max);

        $response = $this->get(route('user.athletes.detail', ['id' => $max->id]));

        $response->assertOk()
            ->assertSee('Advanced')
            ->assertSee('Boxing · Expert')
            ->assertSee('Wednesday')
            ->assertSee('Next bout');
        $this->assertSame(1, $response->viewData('summary')['open_tasks']);
        $this->assertSame(1, $response->viewData('summary')['completed_tasks']);
        $this->assertSame(1, $response->viewData('summary')['completed_sparrings']);
        $this->assertSame(1, $response->viewData('summary')['upcoming_count']);
    }

    public function test_own_profile_page_shows_summary()
    {
        $this->user->userDetail()->create(['experience_level' => 'expert']);

        $this->get(route('user.profile'))->assertOk()->assertSee('Level &amp; skills', false)->assertSee('Expert');
    }

    public function test_onboarding_form_hides_team_skills_until_a_team_exists()
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('user.setting'))->assertOk()->assertSee('Experience level')->assertDontSee('Add skills to the team');
    }
}
