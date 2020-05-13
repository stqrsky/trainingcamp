<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //creating User using factory, then persist them to database, with 15 Users (second arg of factory)

        factory(App\Models\User::class, 15)->create()->each(function($user) {
            $user->roles()->attach(rand(1, 3));
            $skill = rand(1, 5);
            while ($skill < 5) {
                $user->skills()->attach($skill);
                $skill += 1;
            }
        })

        //try to pluck it out of the database 
    }
}
