<?php

namespace App\Models;

use App\Repositories\HistoryRepository;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'description',
        'task_id',
        'due',
        'urgency',
        'asignee_id',
        'checklist_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'created_by'   => 'integer',
        'updated_by'   => 'integer',
        'urgency'      => 'integer',
        'task_id'      => 'integer',
        'asignee_id'   => 'integer',
        'due'          => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function checklist()
    {
        return $this->belongsTo('App\Models\Checklist');
    }

    public function histories()
    {
        return $this->morphMany('App\Models\History', 'loggable');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            HistoryRepository::logCreatedItem($item);
        });

        static::updated(function ($item) {
            HistoryRepository::logUpdatedItem($item);
        });

        static::deleted(function ($item) {
            HistoryRepository::logDeletedItem($item);
        });
    }
}
