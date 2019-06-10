<?php
use Illuminate\Support\Facades\Hash;
use App\Models\Checklist;
use App\Models\User;
use App\Models\Item;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => Hash::make('password'),
    ];
});

$factory->define(Checklist::class, function (Faker\Generator $faker) {
    return [
        'object_id' => $faker->randomDigitNotNull,
        'object_domain' => $faker->word,
        'description' => $faker->sentence(6),
        'task_id' => $faker->randomDigitNotNull,
        'due' => $faker->dateTimeBetween('now', time()),
        'urgency' => $faker->numberBetween(1, 5),
        'created_by' => User::inRandomOrder()->first()->id,
        'updated_by' => null,
        'completed_at' => null
    ];
});

$factory->define(Item::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->sentence(6),
        'task_id' => $faker->randomDigitNotNull,
        'due' => $faker->dateTimeBetween('now', time()),
        'urgency' => $faker->numberBetween(1, 5),
        'created_by' => User::inRandomOrder()->first()->id,
        'updated_by' => null,
        'completed_at' => null,
        'asignee_id' => $faker->randomDigitNotNull,
    ];
});
