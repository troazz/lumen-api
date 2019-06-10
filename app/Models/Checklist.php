<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'object_id',
        'object_domain',
        'description',
        'task_id',
        'due',
        'urgency',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'created_by'   => 'integer',
        'updated_by'   => 'integer',
        'urgency'      => 'integer',
        'task_id'      => 'integer',
        'due'          => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function items()
    {
        return $this->hasMany('App\Models\Item');
    }

    public function history()
    {
        return $this->morphMany('App\Models\History', 'loggable');
    }
}
