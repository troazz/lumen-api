<?php
use App\Models\Checklist;
use App\Repositories\ChecklistRepository;
use App\Repositories\Repository;
use Illuminate\Support\Arr;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\WithoutMiddleware;

class ChecklistEndpointTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testGetAllChecklistSuccess()
    {
        $this->_getAll([
            'uri'   => '/checklists',
            'model' => Checklist::class,
        ]);
    }

    public function testGetAllChecklistWithParamSortSuccess()
    {
        $rep = new ChecklistRepository();
        foreach ($rep->allowed_sort_n_field as $field) {
            // sort asc
            $actualList = Checklist::take(Repository::LIMIT)
                ->offset(Repository::OFFSET)
                ->orderBy($field, 'asc')
                ->get()
                ->pluck('id')->toArray();
            $content = $this->_getAll([
                'uri'    => '/checklists',
                'model'  => Checklist::class,
                'params' => ['sort' => $field],
            ]);
            $responseList = array_pluck($content['data'], 'id');
            $this->assertTrue($responseList == $actualList);

            // sort desc
            $actualList = Checklist::take(Repository::LIMIT)
                ->offset(Repository::OFFSET)
                ->orderBy($field, 'desc')
                ->get()
                ->pluck('id')->toArray();
            $content = $this->_getAll([
                'uri'    => '/checklists',
                'model'  => Checklist::class,
                'params' => ['sort' => "-$field"],
            ]);
            $responseList = array_pluck($content['data'], 'id');
            $this->assertTrue($responseList == $actualList);
        }
    }

    public function testGetAllChecklistWithParamFilterSuccess()
    {
        $rep = new ChecklistRepository();
        foreach ($rep->allowed_filter as $field) {
            // like
            foreach ($rep->allowed_filter_operator as $operator) {
                if ($field == 'is_completed') {
                    switch ($operator) {
                        case 'like':
                        case '!like':
                        case 'is':
                        case '!is':
                            $row = Checklist::inRandomOrder()->first();
                            $q   = $row->$field;
                            break;
                        case 'in':
                        case '!in':
                            $q = Checklist::inRandomOrder()->take(3)->get()->pluck($field)->toArray();
                            $q = array_unique($q);
                            break;
                    }
                } else {
                    switch ($operator) {
                        case 'like':
                        case '!like':
                            $row = Checklist::inRandomOrder()->whereNotNull($field)->first();
                            if ($row) {
                                $qs = explode(' ', $row->$field);
                                $q  = $qs[(rand(1, count($qs)) - 1)];
                            } else {
                                $q = null;
                            }
                            break;
                        case 'is':
                        case '!is':
                            $row = Checklist::inRandomOrder()->whereNotNull($field)->first();
                            if ($row) {
                                $q = $row->$field;
                            } else {
                                $q = null;
                            }
                            break;
                        case 'in':
                        case '!in':
                            $q = Checklist::inRandomOrder()->whereNotNull($field)->take(3)->get()->pluck($field)->toArray();
                            break;
                    }
                }

                if ($q) {
                    switch ($operator) {
                        case 'like':
                            $total = Checklist::where($field, 'like', "%$q%")->get()->count();
                            break;
                        case '!like':
                            $total = Checklist::where($field, 'not like', "%$q%")->get()->count();
                            break;
                        case 'is':
                            $total = Checklist::where($field, $q)->get()->count();
                            break;
                        case '!is':
                            $total = Checklist::where($field, '!=', $q)->get()->count();
                            break;
                        case 'in':
                            $total = Checklist::whereIn($field, $q)->get()->count();
                            $q     = implode(',', $q);
                            break;
                        case '!in':
                            $total = Checklist::whereNotIn($field, $q)->get()->count();
                            $q     = implode(',', $q);
                            break;
                    }

                    $count = $total >= Repository::LIMIT ? Repository::LIMIT : $total;

                    $this->_getAll([
                        'uri'   => "/checklists?filter[$field][$operator]=$q",
                        'total' => $total,
                        'count' => $count,
                    ]);
                }
            }
        }
    }

    public function testGetAllChecklistWithParamFieldsSuccess()
    {
        $rep = new ChecklistRepository();
        foreach ($rep->allowed_sort_n_field as $field) {
            if ($field != 'id') {
                $content = $this->_getAll([
                    'uri'    => '/checklists',
                    'model'  => Checklist::class,
                    'params' => ['fields' => $field],
                ]);
                if ($content['meta']['count']) {
                    foreach ($content['data'] as $data) {
                        $this->assertTrue((array_key_exists($field, $data['attributes']) && count($data['attributes']) == 1));
                    }
                }
            }
        }
    }

    public function testGetAllChecklistWithParamMultipleFieldsSuccess()
    {
        $rep          = new ChecklistRepository();
        $allowedField = array_diff($rep->allowed_sort_n_field, ['id']);

        $fields  = Arr::random($allowedField, rand(1, (count($allowedField) - 1)));
        $content = $this->_getAll([
            'uri'    => '/checklists',
            'model'  => Checklist::class,
            'params' => ['fields' => implode(',', $fields)],
        ]);
        $totalFields = count($fields);
        foreach ($content['data'] as $data) {
            foreach ($fields as $field) {
                $this->assertTrue(array_key_exists($field, $data['attributes']));
            }
            $this->assertTrue(count($data['attributes']) == $totalFields);
        }
    }

    public function testGetAllChecklistWithParamIncludeSuccess()
    {
        $content = $this->_getAll([
            'uri'    => '/checklists',
            'model'  => Checklist::class,
            'params' => ['include' => 'items'],
        ]);
        foreach ($content['data'] as $data) {
            $this->assertTrue(array_key_exists('items', $data['attributes']));
        }
    }

    public function testGetAllParamPageSuccess()
    {
        $total   = Checklist::count();
        $limit   = rand(3, ceil($total / 2));
        $offset  = rand(0, ceil($total / 2));
        $content = $this->_getAll([
            'uri'   => '/checklists?page[limit]=' . $limit . '&page[offset]=' . $offset,
            'total' => $total,
            'count' => $limit,
        ]);
        $actualList = Checklist::take($limit)
            ->offset($offset)
            ->get()
            ->pluck('id')
            ->toArray();
        $responseList = array_pluck($content['data'], 'id');
        $this->assertTrue($responseList == $actualList);
    }

    public function testGetAllChecklistWithWrongParams()
    {
        $this->_getFailed400([
            'uri'    => '/checklists',
            'params' => ['sort' => 'strange_column'],
        ]);
        $this->_getFailed400([
            'uri' => '/checklists?filter[id][notlike]=1',
        ]);
        $this->_getFailed400([
            'uri' => '/checklists?fields=not_column,id',
        ]);
        $this->_getFailed400([
            'uri' => '/checklists?page[limit]=aa&page[offset]=bb',
        ]);
        $this->_getFailed400([
            'uri'    => '/checklists',
            'params' => ['include' => 'foobar'],
        ]);
    }

    public function testGetOneChecklistSuccess()
    {
        $id = Checklist::inRandomOrder()->first()->id;
        $this->_getOne([
            'id'   => $id,
            'uri'  => "/checklists/$id",
            'type' => 'checklists',
        ]);

        $content = $this->_getOne([
            'id'     => $id,
            'uri'    => "/checklists/$id",
            'type'   => 'checklists',
            'params' => ['include' => 'items'],
        ]);
        $this->assertTrue(array_key_exists('items', $content['data']['attributes']));
    }

    public function testGetOneChecklistFailed()
    {
        $id = Checklist::inRandomOrder()->first()->id;
        $this->_getFailed404([
            'uri' => '/checklists/10000',
        ]);
        $this->_getFailed400([
            'uri'    => "/checklists/$id",
            'params' => ['include' => 'foobar'],
        ]);
    }

    public function testCreateSuccess()
    {
        // without items
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'due'           => '2019-01-23 07:50:14',
                    'urgency'       => 1,
                    'description'   => 'Need to verify this guy houses.',
                    'task_id'       => '1231',
                ],
            ],
        ];

        $this->_insert([
            'uri'   => '/checklists',
            'table' => 'checklists',
            'type'  => 'checklists',
            'body'  => $body,
        ]);

        // with items
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'due'           => '2019-01-23 07:50:14',
                    'urgency'       => 1,
                    'description'   => 'Need to verify this guy houses.',
                    'items'         => [
                        'Visit his houses',
                        'Visit his house',
                        'Capture a photo',
                        'Meet him on the house',
                    ],
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_insert([
            'uri'   => '/checklists',
            'type'  => 'checklists',
            'table' => 'checklists',
            'body'  => $body,
        ]);
    }

    public function testCreateFailed()
    {
        // description, object_domain, object_id is null
        $body = [
            'data' => [
                'attributes' => [
                    'due'     => '2019-01-23 07:50:14',
                    'urgency' => 1,
                    'items'   => [
                        'Visit his houses',
                        'Visit his house',
                        'Capture a photo',
                        'Meet him on the house',
                    ],
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => '/checklists',
            'params' => $body,
            'method' => 'POST',
        ]);

        // due datetime format is not correct for validation
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'due'           => '2019-01-23',
                    'urgency'       => 1,
                    'description'   => 'Need to verify this guy houses.',
                    'items'         => [
                        'Visit his houses',
                        'Visit his house',
                        'Capture a photo',
                        'Meet him on the house',
                    ],
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'    => '/checklists',
            'params' => $body,
            'method' => 'POST',
        ]);
    }

    public function testDeleteSuccess()
    {
        $id = Checklist::inRandomOrder()->first()->id;
        $this->_delete([
            'uri'   => "/checklists/$id",
            'id'    => $id,
            'model' => Checklist::class,
        ]);
    }

    public function testDeleteFailed()
    {
        $id = 'foobar';
        $this->_getFailed404([
            'uri'    => "/checklists/$id",
            'method' => 'DELETE',
        ]);
    }

    public function testUpdateSuccess()
    {
        // without items
        $id   = Checklist::inRandomOrder()->first()->id;
        $body = [
            'data' => [
                'attributes' => [
                    'due'     => '2019-01-23 07:50:14',
                    'urgency' => 1,
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_update([
            'uri'   => "/checklists/$id",
            'id'    => $id,
            'model' => Checklist::class,
            'type'  => 'checklists',
            'body'  => $body,
        ]);

        // with items
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'due'           => '2019-01-23 07:50:14',
                    'urgency'       => 1,
                    'description'   => 'Need to verify this guy houses.',
                    'items'         => [
                        'Visit his houses',
                        'Visit his house',
                        'Capture a photo',
                        'Meet him on the house',
                    ],
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_update([
            'uri'   => "/checklists/$id",
            'id'    => $id,
            'model' => Checklist::class,
            'type'  => 'checklists',
            'body'  => $body,
        ]);
    }

    public function testUpdateFailed()
    {
        // description set to null
        $id   = Checklist::inRandomOrder()->first()->id;
        $body = [
            'data' => [
                'attributes' => [
                    'description' => null,
                    'due'     => '2019-01-23 07:50:14',
                    'urgency' => 1,
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'   => "/checklists/$id",
            'params'  => $body,
            'method' => 'PATCH'
        ]);

        // date format is invalid
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'due'           => '2019-01-23',
                    'urgency'       => 1,
                    'description'   => 'Need to verify this guy houses.',
                    'items'         => [
                        'Visit his houses',
                        'Visit his house',
                        'Capture a photo',
                        'Meet him on the house',
                    ],
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed400([
            'uri'   => "/checklists/$id",
            'method' => 'PATCH',
            'params'  => $body,
        ]);

        // id is not found
        $body = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'contact1',
                    'object_id'     => '11',
                    'task_id' => '1231',
                ],
            ],
        ];

        $this->_getFailed404([
            'uri'   => "/checklists/foobar",
            'method' => 'PATCH',
            'params'  => $body,
        ]);
    }
}
