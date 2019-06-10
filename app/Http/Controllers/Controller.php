<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function __construct()
    {
        \DB::listen(function ($query) {
            \Log::info($query->sql, ['Bindings' => $query->bindings, 'Time' => $query->time]);
        });
    }

    public function response($data)
    {
        if (in_array($data['code'], [200, 201])) {
            return response()->json($data['response'], 200);
        } else {
            return response()->json($data, $data['code']);
        }
    }
}
