<?php

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate([
            'title' => 'coach',
            'status' => 1
        ]);
        Role::firstOrCreate([
            'title' => 'athlete',
            'status' => 1
        ]);
        Role::firstOrCreate([
            'title' => 'admin',
            'status' => 1
        ]);
    }
}
