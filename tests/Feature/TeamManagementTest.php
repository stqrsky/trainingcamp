<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $max;
    protected $anna;
    protected $coach;
    protected $boxing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create(['first_name' => 'Owner', 'last_name' => 'Account']);
        $this->user->roles()->attach(Role::where('title', 'coach')->first());
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->team->coaches()->attach($this->user);

        $this->boxing = Skill::create(['name' => 'Boxing', 'team_id' => $this->team->id]);
        $this->max = $this->member('Max', 'Muster', 'Maxi');
        $this->anna = $this->member('Anna', 'Berg', 'Annie');
        $this->max->skills()->attach($this->boxing, ['level' => 'advanced']);
        $this->team->athletes()->attach([$this->max->id, $this->anna->id]);
        $this->coach = $this->member('Carla', 'Coach', 'CC');
        $this->team->coaches()->attach($this->coach);

        $this->actingAs($this->user);
    }

    private function member(string $first, string $last, string $nick): User
    {
        $user = User::factory()->member()->create(['first_name' => $first, 'last_name' => $last]);
        $user->userDetail()->create(['nick_name' => $nick, 'weight' => 70, 'height' => 175]);
        return $user;
    }

    private function listed(array $query = []): array
    {
        $team = $this->get(route('user.athletes', $query))->assertOk()->viewData('team');
        return [
            'coaches' => $team->coaches->pluck('first_name')->all(),
            'athletes' => $team->athletes->pluck('first_name')->all(),
        ];
    }

    private function deactivate(User $athlete): void
    {
        $this->team->athletes()->updateExistingPivot($athlete->id, ['active' => false]);
    }

    public function test_search_matches_last_name_nickname_and_full_name()
    {
        $this->assertSame(['Max'], $this->listed(['search' => 'muster'])['athletes']);
        $this->assertSame(['Anna'], $this->listed(['search' => 'annie'])['athletes']);
        $this->assertSame(['Max'], $this->listed(['search' => 'Max Mus'])['athletes']);
        $this->assertSame(['Carla'], $this->listed(['search' => 'carla'])['coaches']);
    }

    public function test_filter_by_skill_and_sort_by_name()
    {
        $this->assertSame(['Max'], $this->listed(['skill' => $this->boxing->id])['athletes']);
        $this->assertSame(['Max', 'Anna'], $this->listed(['sort' => 'name_desc'])['athletes']);
        $this->assertSame(['Anna', 'Max'], $this->listed(['sort' => 'name'])['athletes']);
    }

    public function test_role_filter_shows_only_that_section()
    {
        $this->get(route('user.athletes', ['role' => 'coach']))
            ->assertSee('Coaches (2)')
            ->assertDontSee('Athletes (');
    }

    public function test_inactive_members_are_hidden_by_default_and_filterable()
    {
        $this->deactivate($this->anna);

        $this->assertSame(['Max'], $this->listed()['athletes']);
        $this->assertSame(['Anna'], $this->listed(['status' => 'inactive'])['athletes']);
        $this->assertSame(['Anna', 'Max'], $this->listed(['status' => 'all'])['athletes']);
    }

    public function test_member_status_can_be_toggled()
    {
        $this->post(route('user.athletes.status', ['id' => $this->anna->id]))->assertRedirect();
        $this->assertFalse((bool) $this->team->athletes()->where('users.id', $this->anna->id)->first()->pivot->active);

        $this->post(route('user.athletes.status', ['id' => $this->anna->id]));
        $this->assertTrue((bool) $this->team->athletes()->where('users.id', $this->anna->id)->first()->pivot->active);
    }

    public function test_coach_member_can_be_edited_and_removed()
    {
        $this->get(route('user.athletes.edit', ['id' => $this->coach->id]))->assertOk();

        $this->put(route('user.athletes.update', ['id' => $this->coach->id]), [
            'first_name' => 'Carla',
            'last_name' => 'Trainer',
            'nick_name' => 'CT',
            'date_of_birth' => '01/01/1990',
            'weight' => 65,
            'height' => 170,
            'skills' => [$this->boxing->id],
        ])->assertRedirect(route('user.athletes'));
        $this->assertEquals('Trainer', $this->coach->fresh()->last_name);

        $this->delete(route('user.athletes.delete', ['id' => $this->coach->id]))->assertRedirect(route('user.athletes'));
        $this->assertFalse($this->team->coaches()->where('users.id', $this->coach->id)->exists());
    }

    public function test_owner_cannot_be_edited_removed_or_deactivated_as_member()
    {
        $this->get(route('user.athletes.edit', ['id' => $this->user->id]))->assertRedirect();
        $this->delete(route('user.athletes.delete', ['id' => $this->user->id]))->assertRedirect();
        $this->post(route('user.athletes.status', ['id' => $this->user->id]))->assertNotFound();

        $this->assertTrue($this->team->activeCoaches()->where('users.id', $this->user->id)->exists());
    }

    public function test_members_of_other_teams_are_not_reachable()
    {
        $stranger = $this->member('Sam', 'Stranger', 'S');
        Team::factory()->create()->athletes()->attach($stranger);

        $this->get(route('user.athletes.edit', ['id' => $stranger->id]))->assertRedirect();
        $this->post(route('user.athletes.status', ['id' => $stranger->id]))->assertNotFound();
    }

    public function test_inactive_athlete_cannot_join_new_sparring_but_stays_on_existing_one()
    {
        $schedule = Schedule::factory()->create(['team_id' => $this->team->id]);
        $schedule->participants()->attach([$this->max->id, $this->anna->id]);
        $this->deactivate($this->anna);

        $payload = [
            'date' => now()->format('d/m/Y'),
            'start' => '18:00',
            'end' => '19:00',
            'first_athlete' => $this->max->id,
            'second_athlete' => $this->anna->id,
        ];

        $this->get(route('schedules.create'))->assertOk()->assertDontSee('Anna Berg');
        $this->post(route('schedules.store'), $payload)->assertSessionHasErrors('second_athlete');
        $this->put(route('schedules.update', $schedule), $payload)->assertSessionHasNoErrors();
    }

    public function test_assign_preselects_athlete_in_sparring_form()
    {
        $this->assertEquals(
            $this->anna->id,
            $this->get(route('schedules.create', ['athlete' => $this->anna->id]))->viewData('first_athlete')
        );
        $this->assertNull($this->get(route('schedules.create', ['athlete' => 999]))->viewData('first_athlete'));
    }

    public function test_inactive_member_cannot_get_new_tasks_but_keeps_existing_ones()
    {
        $task = Task::factory()->create(['team_id' => $this->team->id, 'assignee_id' => $this->anna->id]);
        $this->deactivate($this->anna);

        $this->post(route('tasks.store'), ['title' => 'New for Anna', 'assignee_id' => $this->anna->id])
            ->assertSessionHasErrors('assignee_id');
        $this->put(route('tasks.update', $task), ['title' => 'Still Anna', 'assignee_id' => $this->anna->id])
            ->assertSessionHasNoErrors();
        $this->assertEquals($this->anna->id, $task->fresh()->assignee_id);
    }

    public function test_member_edit_page_has_separate_delete_form()
    {
        $html = $this->get(route('user.athletes.edit', ['id' => $this->max->id]))->getContent();

        $depth = 0;
        preg_match_all('/<form\b|<\/form>/', $html, $tags);
        foreach ($tags[0] as $tag) {
            $depth += $tag === '<form' ? 1 : -1;
            $this->assertLessThanOrEqual(1, $depth, 'Nested <form> elements break the delete button');
        }
        $this->assertStringContainsString('form="member-delete-form"', $html);
    }
}
