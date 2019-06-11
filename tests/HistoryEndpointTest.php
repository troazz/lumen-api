<?php
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\WithoutMiddleware;
use App\Models\History;

class HistoryEndpointTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testGetAllHistorySuccess()
    {
        $this->_getAll([
            'uri' => '/checklists/histories',
            'model' => History::class
        ]);
    }

    public function testGetOneHistorySuccess()
    {
        $id = History::inRandomOrder()->first()->id;
        $this->_getOne([
            'uri' => "/checklists/histories/$id",
            'id' => $id,
            'type' => 'history'
        ]);
    }

    public function testGetOneHistoryFailed()
    {
        $id = 'foobar';
        $this->_getFailed404([
            'uri' => "/checklists/histories/$id",
        ]);
    }
}
