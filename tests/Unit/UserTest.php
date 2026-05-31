<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateUser()
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'Tester',
            'email' => 'test@testtest.de',
            'password' => 'password',
        ];

        $user = User::create($data);

        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['first_name'], $user->first_name);
        $this->assertEquals($data['last_name'], $user->last_name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertTrue(\Hash::check($data['password'], $user->password));
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function testDeleteUser()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

        $user->delete();

        $this->assertDeleted($user);
    }
}
