<?php

namespace App\Repositories;

use App\Models\Checklist;
use App\Models\Item;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class ItemRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
        $this->model                = new Item();
        $this->type                 = 'items';
        $this->allowed_filter       = array_merge(['asignee_id'], $this->allowed_filter);
        $this->allowed_sort_n_field = array_merge(['asignee_id'], $this->allowed_sort_n_field);
        $this->addRules             = [
            'description' => 'required',
            'asignee_id'  => 'nullable|numeric',
            'task_id'     => 'nullable|numeric',
            'due'         => 'nullable|date_format:Y-m-d H:i:s',
            'urgency'     => 'nullable|numeric',
        ];
        $this->updateRules = [
            'description' => 'nullable',
            'asignee_id'  => 'nullable|numeric',
            'task_id'     => 'nullable|numeric',
            'due'         => 'nullable|date_format:Y-m-d H:i:s',
            'urgency'     => 'nullable|numeric',
        ];
    }

    public function getOne($id, $params)
    {
        $checklistId = $params['checklistId'];
        try {
            $row = $this->model;
            $row = $row->whereId($id)->where('checklist_id', $checklistId)->firstOrFail();

            $row      = $this->formatRow($row->toArray(), 'items');
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

        return $response;
    }

    public function complete($data)
    {
        return $this->_updateCompletion($data, true);
    }

    public function incomplete($data)
    {
        return $this->_updateCompletion($data, false);
    }

    public function create($id, $data)
    {
        $errors = $this->validateData($data, 'add');
        if (! $errors) {
            $data = $data['attributes'];
            try {
                $checklist = Checklist::whereId($id)->firstOrFail();
                $row       = $this->model;
                $row->fill($data);
                $row->created_by   = $this->me->id;
                $row->checklist_id = $id;
                $row->save();

                $checklist->load('items');
                ChecklistRepository::checkComplete($checklist);
                $row = $this->formatRow($row->toArray(), 'items');

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

    public function update($checklistId, $itemId, $data, $bulk = false)
    {
        if (! $bulk) {
            $errors = $this->validateData($data, 'update');
        } else {
            $errors = false;
        }

        if (! $errors) {
            $data = $data['attributes'];
            try {
                $row = $this->model
                    ->whereId($itemId)
                    ->with('checklist')
                    ->where('checklist_id', $checklistId)
                    ->firstOrFail();
                $row->fill($data);
                $row->updated_by = $this->me->id;
                $row->save();

                $row->checklist->load('items');
                ChecklistRepository::checkComplete($row->checklist);
                $row = $this->formatRow($row->toArray(), 'items');

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

    public function bulk($checklistId, $data)
    {
        if (! $data || ! is_array($data)) {
            return [
                'code'  => 400,
                'error' => [
                    'data' => 'Data not specified correctly',
                ],
            ];
        }
        $result = [];
        $rules  = ['id' => 'required|numeric'];
        foreach ($this->updateRules as $field => $rule) {
            $rules["attributes.$field"] = $rule;
        }
        foreach ($data as $item) {
            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                $code = 400;
            } else {
                $response = $this->update($checklistId, $item['id'], $item);
                $code     = $response['code'];
            }
            $result[] = [
                'id'     => @$item['id'],
                'action' => 'update',
                'status' => $code,
            ];
        }

        return [
            'code'     => 200,
            'response' => ['data' => $result],
        ];
    }

    public function delete($id, $params = [])
    {
        try {
            $row = $this->model
                    ->whereId($id)
                    ->where('checklist_id', $params['checklistId'])
                    ->with('checklist')
                    ->firstOrFail();
            $row->delete();

            $row->checklist->load('items');
            ChecklistRepository::checkComplete($row->checklist);

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

    private function _updateCompletion($data, $is_complete)
    {
        $errors = $this->_validateDataCompletion($data);
        if (! $errors) {
            $data = $data['data'];
            try {
                $result        = [];
                $checklists_id = [];
                foreach ($data as $item) {
                    $row = $this->model->find($item['item_id']);
                    if ($row) {
                        $row->completed_at = $is_complete ? Carbon::now()->format('Y-m-d H:i:s') : null;
                        $row->is_completed = $is_complete;
                        $row->updated_by   = $this->me->id;
                        $row->save();

                        if (! in_array($row->checklist_id, $checklists_id)) {
                            $checklists_id[] = $row->checklist_id;
                        }

                        $result[] = [
                            'id'           => $row->id,
                            'item_id'      => $row->id,
                            'is_completed' => $is_complete,
                            'checklist_id' => $row->checklist_id,
                        ];
                    }
                }

                $checklists = Checklist::with('items')->whereIn('id', $checklists_id)->get();
                foreach ($checklists as $checklist) {
                    ChecklistRepository::checkComplete($checklist);
                }

                $response = [
                    'code'     => '200',
                    'response' => ['data' => $result],
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

    private function _validateDataCompletion($data)
    {
        if (empty($data)) {
            return ['attributes' => 'Attributes not specified correctly'];
        }
        $validator = Validator::make($data, [
            'data.*.item_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return false;
    }
}
