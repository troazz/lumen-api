<?php

namespace App\Repositories;

use App\Models\Checklist;
use App\Models\Item;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChecklistRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
        $this->model                = new Checklist();
        $this->type                 = 'checklists';
        $this->allowed_filter       = array_merge(['object_domain', 'object_id'], $this->allowed_filter);
        $this->allowed_sort_n_field = array_merge(['object_domain', 'object_id'], $this->allowed_sort_n_field);
        $this->addRules             = [
            'object_id'     => 'required',
            'object_domain' => 'required',
            'description'   => 'required',
            'task_id'       => 'nullable|numeric',
            'items'         => 'nullable|array',
            'due'           => 'nullable|date_format:Y-m-d H:i:s',
            'urgency'       => 'nullable|numeric',
        ];
        $this->updateRules = [
            'object_id'     => 'nullable',
            'object_domain' => 'nullable',
            'description'   => 'nullable',
            'items'         => 'nullable|array',
            'task_id'       => 'nullable|numeric',
            'due'           => 'nullable|date_format:Y-m-d H:i:s',
            'urgency'       => 'nullable|numeric',
        ];
    }

    public function create($data)
    {
        $errors = $this->validateData($data, 'add');
        if (! $errors) {
            $data = $data['attributes'];
            try {
                $row = $this->model;
                $row->fill($data);
                $row->created_by = $this->me->id;
                $row->save();

                if (! empty($data['items'])) {
                    foreach ($data['items'] as $desc) {
                        $item              = new Item($data);
                        $item->description = $desc;
                        $item->created_by  = $this->me->id;
                        $row->items()->save($item);
                    }
                    $row->load('items');
                }

                $row      = $this->formatRow($row->toArray(), 'checklists');
                $response = [
                    'code'     => '201',
                    'response' => ['data' => $row],
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

    public function update($id, $data)
    {
        $errors = $this->validateData($data, 'update');
        if (! $errors) {
            $data = $data['attributes'];
            try {
                $row = $this->model->whereId($id)->firstOrFail();
                $row->fill($data);
                $row->updated_by = $this->me->id;
                $row->save();

                if (! empty($data['items'])) {
                    Item::where('checklist_id', $row->id)->delete();
                    $row->completed_at = null;
                    $row->is_completed = 0;
                    $row->save();

                    foreach ($data['items'] as $desc) {
                        $item              = new Item($data);
                        $item->description = $desc;
                        $item->created_by  = $this->me->id;
                        $row->items()->save($item);
                    }
                    $row->load('items');
                }

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
                dd($e);
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

    public static function checkComplete(Checklist &$checklist)
    {
        $is_completed = true;
        foreach ($checklist->items as $item) {
            if (! $item->is_completed) {
                $is_completed = false;
                break;
            }
        }

        if ($checklist->is_completed != $is_completed) {
            $checklist->is_completed = $is_completed;
            $checklist->completed_at = $is_completed ? Carbon::now()->format('Y-m-d H:i:s') : null;
            $checklist->updated_by   = Auth::user()->id;
            $checklist->save();
        }
    }
}
