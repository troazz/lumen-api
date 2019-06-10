<?php

namespace App\Repositories;

use Auth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class Repository
{
    protected $model;
    protected $type;
    protected $allowed_include         = ['items'];
    protected $allowed_filter          = ['description', 'is_completed', 'completed_at'];
    protected $allowed_filter_operator = ['like', '!like', 'is', '!is', 'in', '!in'];
    protected $allowed_sort_n_field    = [
        'description',
        'task_id',
        'due',
        'urgency',
        'created_by',
        'updated_by',
        'is_completed',
        'completed_at',
        'created_at',
        'updated_at',
    ];
    protected $addRules;
    protected $updateRules;
    protected $me;

    public function __construct()
    {
        $this->me = Auth::user();
    }

    public function get($params)
    {
        $errors = $this->validateParams($params);
        if (! $errors) {
            try {
                $rows     = $this->getRows($params);
                $response = [
                    'code'     => '200',
                    'response' => $rows,
                ];
            } catch (Exception $e) {
                $response = [
                    'code'  => '500',
                    'error' => 'Server Error',
                ];
            }
        } else {
            $response = [
                'code'  => '400',
                'error' => $errors,
            ];
        }

        return $response;
    }

    public function getOne($id, $params)
    {
        $errors = $this->validateParams($params);
        if (! $errors) {
            try {
                $row = $this->model;
                if (! empty($params['include']) && $params['include'] == 'items') {
                    $row = $row->with('items');
                }
                $row = $row->whereId($id)->firstOrFail();

                $row      = $this->formatRow($row->toArray(), 'checklists');
                $response = [
                    'code'     => '200',
                    'response' => ['data' => $row],
                ];
            } catch (ModelNotFoundException $e) {
                $response = [
                    'code'  => '404',
                    'error' => 'Not Found',
                ];
            } catch (Exception $e) {
                $response = [
                    'code'  => '500',
                    'error' => 'Server Error',
                ];
            }
        } else {
            $response = [
                'code'  => '400',
                'error' => $errors,
            ];
        }

        return $response;
    }

    public function delete($id, $params = [])
    {
        try {
            $row = $this->model;
            $row = $row->whereId($id)->firstOrFail();
            $row->delete();

            $response = [
                'code'     => '204',
                'response' => [],
            ];
        } catch (ModelNotFoundException $e) {
            $response = [
                'code'  => '404',
                'error' => 'Not Found',
            ];
        } catch (Exception $e) {
            $response = [
                'code'  => '500',
                'error' => 'Server Error',
            ];
        }

        return $response;
    }

    public function formatRows($rows, $type)
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->formatRow($row, $type);
        }

        return $result;
    }

    public function formatRow($row, $type)
    {
        $id = $row['id'];
        unset($row['id']);
        if ($type == 'checklists') {
            $self = route('checklists.detail', ['checklistId' => $id]);
        } else {
            unset($row['checklist']);
            $self = route('items.detail', ['checklistId' => $row['checklist_id'], 'itemId' => $id]);
        }
        $result = [
            'type'       => $type,
            'id'         => $id,
            'attributes' => $row,
            'links'      => [
                'self' => $self,
            ],
        ];

        return $result;
    }

    protected function validateParams($params)
    {
        $validator = Validator::make($params, [
            'include' => 'nullable|in:' . implode($this->allowed_include),
            'filter'  => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $k => $v) {
                        if (! in_array($k, $this->allowed_filter)) {
                            $fail("$attribute $k is not available.");
                            break;
                        }
                        foreach ($v as $operator => $real_value) {
                            if (! in_array($operator, $this->allowed_filter_operator)) {
                                $fail("$attribute operator $operator is not available.");
                                break;
                            }
                        }
                    }
                },
            ],
            'sort' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $value = str_replace('-', '', $value);
                    if (! in_array($value, $this->allowed_sort_n_field)) {
                        $fail("Field $value is not available for sorting.");
                    }
                },
            ],
            'fields' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $fields = explode(',', $value);
                    foreach ($fields as $v) {
                        if (! in_array($v, $this->allowed_sort_n_field)) {
                            $fail("Field $v is not available.");
                            break;
                        }
                    }
                },
            ],
            'page.limit'  => 'nullable|numeric',
            'page.offset' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return false;
    }

    protected function validateData($data, $action)
    {
        if ($action == 'add') {
            $rules = $this->addRules;
        } else {
            $rules = $this->updateRules;
        }

        if (empty($data['attributes'])) {
            return ['attributes' => 'Attributes not specified correctly'];
        }

        $validator = Validator::make($data['attributes'], $rules);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return false;
    }

    protected function getRows($params)
    {
        $limit  = empty($params['page']['limit']) ? 10 : $params['page']['limit'];
        $offset = empty($params['page']['offset']) ? 0 : $params['page']['offset'];
        $rows   = $this->model;

        if (! empty($params['filter'])) {
            foreach ($params['filter'] as $field => $v) {
                foreach ($v as $operator => $value) {
                    switch ($operator) {
                        case 'like':
                            if (stripos($value, '*') === false && stripos($value, '%') === false) {
                                $value = "%$value%";
                            }
                            $rows = $rows->where($field, 'like', $value);
                            break;
                        case '!like':
                            if (stripos($value, '*') === false && stripos($value, '%') === false) {
                                $value = "%$value%";
                            }
                            $rows = $rows->where($field, 'not like', $value);
                            break;
                        case 'is':
                            $rows = $rows->where($field, '=', $value);
                            break;
                        case '!is':
                            $rows = $rows->where($field, '!=', $value);
                            break;
                        case 'in':
                            $rows = $rows->whereIn($field, explode(',', $value));
                            break;
                        case '!in':
                            $rows = $rows->whereNotIn($field, explode(',', $value));
                            break;
                    }
                }
            }
        }

        $rowsCount = clone $rows;
        $rowsCount = $rowsCount->count();

        if (! empty($params['include']) && $params['include'] == 'items') {
            $rows = $rows->with('items');
        }

        if (! empty($params['fields'])) {
            $fields = explode(',', $params['fields']);
            if (! in_array('id', $fields)) {
                $fields[] = 'id';
            }
            $rows = $rows->select($fields);
        }

        if (! empty($params['sort'])) {
            $dir   = stripos($params['sort'], '-') === 0 ? 'desc' : 'asc';
            $field = str_replace('-', '', $params['sort']);
            $rows  = $rows->orderBy($field, $dir);
        } else {
            $rows = $rows->orderBy('id', 'asc');
        }

        $rows      = $rows->take($limit)->offset($offset)->get();
        $totalPage = ceil($rowsCount / $limit);
        $maxOffset = (($totalPage - 1) * $limit);
        $newParams = $params;
        unset($newParams['page']);

        $result = [
            'meta' => [
                'count' => $rows->count(),
                'total' => $rowsCount,
            ],
            'links' => [
                'first' => $rowsCount > 0 ? route($this->type, array_merge($newParams, ['page' => ['limit' => $limit, 'offset' => 0]])) : null,
                'last'  => $maxOffset > 0 ? route($this->type, array_merge($newParams, ['page' => ['limit' => $limit, 'offset' => $maxOffset]])) : null,
                'next'  => ($maxOffset > $offset) ? route($this->type, array_merge($newParams, ['page' => ['limit' => $limit, 'offset' => ($offset + $limit)]])) : null,
                'prev'  => $offset > 0 ? route($this->type, array_merge($newParams, ['page' => ['limit' => $limit, 'offset' => ($offset - $limit ?: 0)]])) : null,
            ],
            'data' => $this->formatRows($rows->toArray(), $this->type),
        ];

        return $result;
    }
}
