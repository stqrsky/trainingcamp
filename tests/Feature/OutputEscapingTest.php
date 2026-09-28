<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutputEscapingTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<script>alert("xss")</script>';

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

    public function test_post_description_is_escaped_on_home()
    {
        Notification::create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'title' => 'Announcement',
            'description' => self::PAYLOAD,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
        $response->assertSee(self::PAYLOAD);
    }

    public function test_athlete_about_is_escaped_on_detail_page()
    {
        $athlete = User::factory()->create();
        $athlete->userDetail()->create(['about' => self::PAYLOAD, 'weight' => 72, 'height' => 180]);
        $this->team->athletes()->attach($athlete);

        $response = $this->get(route('user.athletes.detail', ['id' => $athlete->id]));

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
        $response->assertSee('72 kg');
    }

    public function test_about_cannot_break_out_of_textarea_on_edit_form()
    {
        $breakout = '</textarea>' . self::PAYLOAD;
        $athlete = User::factory()->create();
        $athlete->userDetail()->create(['about' => $breakout]);
        $this->team->athletes()->attach($athlete);

        $response = $this->get(route('user.athletes.edit', ['id' => $athlete->id]));

        $response->assertOk();
        $response->assertDontSee($breakout, false);
    }

    public function test_own_about_is_escaped_on_profile()
    {
        $this->user->userDetail()->create(['about' => self::PAYLOAD]);

        $response = $this->get(route('user.profile'));

        $response->assertOk();
        $response->assertDontSee(self::PAYLOAD, false);
    }
}
