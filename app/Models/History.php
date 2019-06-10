<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'action',
        'uid',
        'value',
    ];

    protected $casts = [
        'action' => 'string',
        'uid'    => 'integer',
        'value'  => 'string',
    ];

    public function loggable()
    {
        return $this->morphTo();
    }
}
