<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTeamTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $teamA;
    protected $teamB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->teamA = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha']);
        $this->teamB = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Bravo']);

        $this->actingAs($this->user);
    }

    public function test_current_team_defaults_to_oldest_own_team()
    {
        $this->assertTrue($this->user->currentTeam()->is($this->teamA));
    }

    public function test_stale_current_team_falls_back_to_own_team()
    {
        $foreignTeam = Team::factory()->create();
        $this->user->update(['current_team_id' => $foreignTeam->id]);

        $this->assertTrue($this->user->fresh()->currentTeam()->is($this->teamA));
    }

    public function test_user_can_switch_to_own_team()
    {
        $response = $this->from(route('tasks.index'))->post(route('teams.switch', $this->teamB));

        $response->assertRedirect(route('tasks.index'));
        $this->assertEquals($this->teamB->id, $this->user->fresh()->current_team_id);
    }

    public function test_user_cannot_switch_to_foreign_team()
    {
        $foreignTeam = Team::factory()->create();

        $this->post(route('teams.switch', $foreignTeam))->assertNotFound();

        $this->assertNull($this->user->fresh()->current_team_id);
    }

    public function test_data_is_scoped_to_current_team()
    {
        $taskA = Task::factory()->create(['team_id' => $this->teamA->id, 'title' => 'Alpha task']);
        Task::factory()->create(['team_id' => $this->teamB->id, 'title' => 'Bravo task']);
        $this->user->switchTeam($this->teamB);

        $response = $this->get(route('tasks.index'));

        $response->assertSee('Bravo task');
        $response->assertDontSee('Alpha task');
        $this->get(route('tasks.edit', $taskA))->assertForbidden();
    }

    public function test_new_tasks_land_in_current_team()
    {
        $this->user->switchTeam($this->teamB);

        $this->post(route('tasks.store'), ['title' => 'Team B task']);

        $this->assertDatabaseHas('tasks', ['title' => 'Team B task', 'team_id' => $this->teamB->id]);
    }

    public function test_posts_are_scoped_to_current_team()
    {
        Notification::create([
            'user_id' => $this->user->id,
            'team_id' => $this->teamA->id,
            'title' => 'Alpha news',
            'description' => 'For Alpha',
        ]);
        $this->user->switchTeam($this->teamB);

        $this->get(route('home'))->assertOk()->assertDontSee('Alpha news');
    }

    public function test_user_can_create_additional_team()
    {
        $response = $this->post(route('teams.store'), ['name' => 'Charlie', 'description' => 'Juniors']);

        $response->assertRedirect(route('user.athletes'));
        $team = Team::where('name', 'Charlie')->firstOrFail();
        $this->assertEquals($this->user->id, $team->user_id);
        $this->assertTrue($team->coaches->contains($this->user));
        $this->assertEquals($team->id, $this->user->fresh()->current_team_id);
    }

    public function test_user_can_rename_current_team_only()
    {
        $this->user->switchTeam($this->teamB);

        $this->put(route('teams.update'), ['name' => 'Bravo Renamed'])->assertRedirect(route('user.athletes'));

        $this->assertEquals('Bravo Renamed', $this->teamB->fresh()->name);
        $this->assertEquals('Alpha', $this->teamA->fresh()->name);
    }

    public function test_header_shows_team_switcher_with_own_teams_only()
    {
        Team::factory()->create(['name' => 'Foreign Squad']);

        $response = $this->get(route('tasks.index'));

        $response->assertSee('Alpha')->assertSee('Bravo')->assertDontSee('Foreign Squad');
    }

    public function test_profile_update_does_not_touch_teams()
    {
        $skill = Skill::create(['name' => 'Boxing']);
        $member = User::factory()->member()->create();
        $this->teamA->coaches()->attach([$this->user->id, $member->id]);

        $this->put(route('user.profile.setting.put'), [
            'first_name' => 'Vera',
            'last_name' => 'Coach',
            'nick_name' => 'V',
            'date_of_birth' => '01/01/1990',
            'weight' => 70,
            'height' => 175,
            'skills' => [$skill->id],
        ])->assertRedirect(route('user.profile'));

        $this->assertEquals('Alpha', $this->teamA->fresh()->name);
        $this->assertTrue($this->teamA->coaches()->where('users.id', $member->id)->exists());
    }

    public function test_member_is_created_without_login()
    {
        $skill = Skill::create(['name' => 'Boxing']);

        $response = $this->post(route('user.athletes.post'), [
            'user_type' => 'athlete',
            'first_name' => 'Max',
            'last_name' => 'Muster',
            'nick_name' => 'Maxi',
            'date_of_birth' => '01/01/2000',
            'weight' => 72,
            'height' => 180,
            'skills' => [$skill->id],
        ]);

        $response->assertRedirect(route('user.athletes'));
        $member = User::where('first_name', 'Max')->firstOrFail();
        $this->assertNull($member->email);
        $this->assertNull($member->password);
        $this->assertFalse($member->login_enabled);
        $this->assertTrue($this->teamA->athletes->contains($member));
    }

    public function test_member_cannot_log_in_even_with_password()
    {
        auth()->logout();
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'secret123',
            'login_enabled' => false,
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'member@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertGuest();
    }

    public function test_onboarding_is_skipped_once_a_team_exists()
    {
        $this->get(route('user.setting'))->assertRedirect(route('home'));
    }
}
