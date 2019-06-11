<?php
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

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
