<?php

namespace App\Repositories;

use App\Models\Checklist;
use App\Models\History;
use App\Models\Item;
use Auth;

class HistoryRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
        $this->model                = new History();
        $this->type                 = 'history';
        $this->allowed_sort_n_field = $this->allowed_filter = [
            'loggable_type',
            'loggable_id',
            'action',
            'uid',
            'value',
        ];
    }

    public static function logUpdatedItem(Item $item)
    {
        $changes = $item->getChanges();
        foreach ($changes as $field => $value) {
            if (in_array($field, ['is_completed', 'updated_at', 'created_by', 'created_by', 'updated_by'])) {
                continue;
            }
            $action = "update:$field";
            if ($field == 'asignee_id' && $value) {
                $action = 'assign';
            }
            if ($field == 'asignee_id' && ! $value) {
                $action = 'unassign';
            }
            if ($field == 'completed_at' && $value) {
                $action = 'completed';
            }
            if ($field == 'completed_at' && ! $value) {
                $action = 'incompleted';
            }

            self::saveLog($item, $action, $value);
        }
    }

    public static function logUpdatedChecklist(Checklist $checklist)
    {
        $changes = $checklist->getChanges();
        foreach ($changes as $field => $value) {
            if (in_array($field, ['is_completed', 'updated_at', 'created_by', 'created_by', 'updated_by'])) {
                continue;
            }
            $action = "update:$field";
            if ($field == 'completed_at' && $value) {
                $action = 'completed';
            }
            if ($field == 'completed_at' && ! $value) {
                $action = 'incompleted';
            }

            self::saveLog($checklist, $action, $value);
        }
    }

    public static function logCreatedChecklist(Checklist $checklist)
    {
        self::saveLog($checklist, 'created', $checklist->id);
    }

    public static function logCreatedItem(Item $item)
    {
        self::saveLog($item, 'created', $item->id);
    }

    public static function saveLog($model, $action, $value)
    {
        $history         = new History();
        $history->action = $action;
        $history->uid    = Auth::user()->id;
        $history->value  = $value;

        $model->histories()->save($history);
    }
}