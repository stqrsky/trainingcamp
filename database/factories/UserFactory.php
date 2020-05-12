<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    $first_name = $faker->firstName();
    return [
        'first_name' => $first_name,
        'last_name' => $faker->lastName(),
        'nick_name' => $first_name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => \Hash::make('secret'), // password
        'date_of_birth' => Carbon::now()->day(rand(1, 25))->month(rand(1, 12))->year(rand(1990, 2000))->format('Y-m-d'),
        'about' => $faker->text(200),
        'status' => 1,
        'remember_token' => Str::random(10),
    ];
});
