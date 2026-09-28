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

    public function test_login_rotates_csrf_token()
    {
        User::factory()->create(['email' => 'fixation@example.com', 'password' => 'secret']);
        $this->withSession(['_token' => 'pre-login-token']);

        $this->post(route('login.post'), [
            '_token' => 'pre-login-token',
            'email' => 'fixation@example.com',
            'password' => 'secret',
        ]);

        $this->assertAuthenticated();
        $this->assertNotEquals('pre-login-token', session()->token());
    }

    public function test_failed_login_does_not_flash_password()
    {
        User::factory()->create(['email' => 'flash@example.com', 'password' => 'secret']);

        $response = $this->post(route('login.post'), [
            'email' => 'flash@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertGuest();
        $this->assertSame('flash@example.com', session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput('password'));
        $this->get(route('login'))->assertSee('value="flash@example.com"', false);
    }

    public function test_login_is_throttled_after_too_many_failed_attempts()
    {
        User::factory()->create(['email' => 'throttle@example.com', 'password' => 'secret']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.post'), [
            'email' => 'throttle@example.com',
            'password' => 'secret',
        ]);

        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('error'));
    }

    public function test_register_requires_minimum_password_length()
    {
        $response = $this->post(route('signup.post'), [
            'email' => 'short@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'short@example.com']);
    }

    public function test_logout_invalidates_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['tc_marker' => 'stale', '_token' => 'pre-logout-token']);

        $response = $this->post(route('user.logout'), ['_token' => 'pre-logout-token']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertFalse(session()->has('tc_marker'));
        $this->assertNotEquals('pre-logout-token', session()->token());
    }

    public function test_home_requires_authentication()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }
}
