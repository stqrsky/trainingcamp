<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;    //clear database before go through test

    public function testCreateUser()
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'Tester',
            'email' => 'test@testtest.de',
            'password' => 'password',
            'updated_at' => '2029-12-01 00:00:00',
            'created_at' => '2029-12-01 00:00:00'
        ];

        $user = User::create($data);

        $this->assertNotnull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['first_name'], $user->first_name);
        $this->assertEquals($data['last_name'], $user->last_name);
        $this->assertEquals($data['email'], $user->email);
        $this->attributes['password'] = bcrypt($user->password);
        $this->assertEquals($data['updated_at'], $user->updated_at);
        $this->assertEquals($data['created_at'], $user->created_at);
    }

    public function testDeleteUser()
    {
        $user = factory(User::class)->create();

        $this->assertDatabaseHas('users', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        ]);

        $user->delete();

        $this->assertDeleted($user);
    }
}
