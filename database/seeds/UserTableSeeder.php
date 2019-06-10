<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('email', 'admin@here.com')->first();
        if (!$user) {
            factory(User::class)->create([
                'email' => 'admin@here.com'
            ]);
        }
        factory(User::class, 5)->create();
    }
}
