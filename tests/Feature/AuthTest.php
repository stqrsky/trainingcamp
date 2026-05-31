<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_can_view_signup_page()
    {
        $response = $this->get(route('signup'));

        $response->assertStatus(200);
    }

    public function test_user_can_register()
    {
        $response = $this->post(route('signup.post'), [
            'email' => 'coach@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('user.setting'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'coach@example.com']);
        $this->assertTrue(
            User::where('email', 'coach@example.com')->first()->roles()->where('title', 'coach')->exists()
        );
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret',
        ]);
        $user->roles()->attach(Role::where('title', 'coach')->first());

        $response = $this->post(route('login.post'), [
            'email' => 'login@example.com',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_home_requires_authentication()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }
}
