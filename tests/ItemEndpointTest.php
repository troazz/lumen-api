<?php

use App\Models\Checklist;
use App\Models\Item;
use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\WithoutMiddleware;

class ItemEndpointTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testGetAllItemSuccess()
    {
        $checklistId = Checklist::whereHas('items')->inRandomOrder()->first()->id;
        $this->_getOne([
            'uri'  => "/checklists/$checklistId/items",
            'id'   => $checklistId,
            'type' => 'checklists',
        ]);
    }

    public function testGetAllItemFailed()
    {
        $this->_getFailed404([
            'uri' => '/checklists/foobar/items',
        ]);
    }

    public function testCompleteItemsSuccess()
    {
        $this->_sendCompletion(true);
    }

    public function testInCompleteItemsSuccess()
    {
        $this->_sendCompletion(false);
    }

    public function testCompletionFailed()
    {
        $body  = [];
        $items = Item::select('id')->take(5)->inRandomOrder()->get();
        foreach ($items as $item) {
            $body[] = ['item_id' => $item->id];
        }
        $this->_getFailed400([
            'uri'    => '/checklists/complete',
            'params' => $body,
            'method' => 'POST',
        ]);
        $this->_getFailed400([
            'uri'    => '/checklists/incomplete',
            'method' => 'POST',
            'params' => $body,
        ]);
    }

    public function testGetOneItemSuccess()
    {
        $row = Item::inRandomOrder()->first();
        $this->_getOne([
            'id'   => $row->id,
            'uri'  => "/checklists/{$row->checklist_id}/items/{$row->id}",
            'type' => 'items',
        ]);
    }

    public function testGetOneItemFailed()
    {
        $row = Item::inRandomOrder()->first();
        $this->_getFailed404([
            'uri' => "/checklists/foobar/items/{$row->id}",
        ]);
        $this->_getFailed404([
            'uri' => "/checklists/{$row->checklist_id}/items/foobar",
        ]);
    }

    public function testCreateSuccess()
    {
        $checklistId = Checklist::inRandomOrder()->first()->id;
        $body        = [
            'data' => [
                'attributes' => [
                    'description' => 'Need to verify this guy houses.',
                    'due'         => '2019-01-23 07:50:14',
                    'urgency'     => 1,
                    'task_id'     => '1231',
                ],
            ],
        ];

        $this->_insert([
            'uri'  => "/checklists/$checklistId/items",
            'type' => 'items',
            'body' => $body,
        ]);
    }

    public function testCreateFailed()
    {
        $checklistId = Checklist::inRandomOrder()->first()->id;
        // description is null
        $body = [
            'data' => [
                'attributes' => [
                    'due'     => '2019-01-23 07:50:14',
                    'urgency' => 1,
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => "/checklists/$checklistId/items",
            'params' => $body,
            'method' => 'POST',
        ]);

        // due datetime format is not correct for validation
        $body = [
            'data' => [
                'attributes' => [
                    'due'         => '2019-01-23',
                    'urgency'     => 1,
                    'description' => 'Need to verify this guy houses.',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => "/checklists/$checklistId/items",
            'params' => $body,
            'method' => 'POST',
        ]);
    }

    public function testDeleteSuccess()
    {
        $row = Checklist::with('items')->whereHas('items')->inRandomOrder()->first();
        $id  = $row->items->first()->id;
        $this->_delete([
            'uri'   => "/checklists/{$row->id}/items/$id",
            'id'    => $id,
            'model' => Item::class,
        ]);
    }

    public function testDeleteFailed()
    {
        $row = Item::inRandomOrder()->first();
        $this->_getFailed404([
            'uri'    => "/checklists/foobar/items/{$row->id}",
            'method' => 'DELETE',
        ]);
        $this->_getFailed404([
            'uri'    => "/checklists/{$row->checklist_id}/items/foobar",
            'method' => 'DELETE',
        ]);
    }

    public function testUpdateSuccess()
    {
        $row  = Checklist::with('items')->whereHas('items')->inRandomOrder()->first();
        $id   = $row->items->first()->id;
        $body = [
            'data' => [
                'attributes' => [
                    'description' => 'Need to verify this guy houses.',
                    'due'         => '2019-01-23 07:50:14',
                    'urgency'     => 1,
                    'task_id'     => '1231',
                ],
            ],
        ];

        $this->_update([
            'uri'   => "/checklists/{$row->id}/items/$id",
            'type'  => 'items',
            'id'    => $id,
            'model' => Item::class,
            'body'  => $body,
        ]);
    }

    public function testUpdateFailed()
    {
        // description set to null
        $row  = Checklist::with('items')->whereHas('items')->inRandomOrder()->first();
        $id   = $row->items->first()->id;
        $body = [
            'data' => [
                'attributes' => [
                    'description' => null,
                    'due'         => '2019-01-23 07:50:14',
                    'urgency'     => 1,
                    'task_id'     => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => "/checklists/{$row->id}/items/$id",
            'params' => $body,
            'method' => 'PATCH',
        ]);

        // date format is invalid
        $body = [
            'data' => [
                'attributes' => [
                    'due'        => '2019-01-23',
                    'asignee_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => "/checklists/{$row->id}/items/$id",
            'method' => 'PATCH',
            'params' => $body,
        ]);

        // id is not found
        $body = [
            'data' => [
                'attributes' => [
                    'asignee_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed404([
            'uri'    => "/checklists/foobar/items/$id",
            'method' => 'PATCH',
            'params' => $body,
        ]);

        $this->_getFailed404([
            'uri'    => "/checklists/{$row->id}/items/foobar",
            'method' => 'PATCH',
            'params' => $body,
        ]);
    }

    public function testBulkUpdateSuccess()
    {
        $body  = ['data' => []];
        $checklist = Checklist::whereHas('items')->inRandomOrder()->first();
        $items = $checklist->items;
        foreach ($items as $item) {
            $body['data'][] = [
                'id'         => $item->id,
                'action'     => 'update',
                'attributes' => [
                    'description' => 'this is bulk edited 1',
                    'due'         => '2019-01-19 18:34:51',
                    'urgency'     => '2',
                ],
            ];
        }

        $this->json('POST', "/checklists/{$checklist->id}/items/_bulk", $body)
            ->assertResponseOk();
    }

    private function _sendCompletion($is_complete)
    {
        $body  = ['data' => []];
        $items = Item::select('id')->take(5)->inRandomOrder()->get();
        foreach ($items as $item) {
            $body['data'][] = ['item_id' => $item->id];
        }

        $user = User::inRandomOrder()->first();

        $uri = $is_complete ? '/checklists/complete' : '/checklists/incomplete';
        $this->actingAs($user)
            ->json('POST', $uri, $body)
            ->assertResponseOk();

        $items = Item::select('id', 'is_completed')->whereIn('id', $items->pluck('id')->toArray())->get();
        foreach ($items as $item) {
            $this->assertTrue($item->is_completed == $is_complete);
        }
    }
}
