<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\WithoutMiddleware;

class ChecklistRepositoryTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    public function testWithCredetial()
    {
        $user = User::inRandomOrder()->first();
        $this->actingAs($user)
            ->json('GET', '/checklists')
            ->assertResponseOk();
    }

    public function testWithoutCredetial()
    {
        $this->json('GET', '/checklists')
            ->assertResponseStatus(401);
    }
}
