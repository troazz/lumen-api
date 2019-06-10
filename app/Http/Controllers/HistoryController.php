<?php

namespace App\Http\Controllers;

use Cache;
use Illuminate\Http\Request;
use App\Repositories\HistoryRepository;

class HistoryController extends Controller
{
    private $_rep;

    public function __construct()
    {
        parent::__construct();
        $this->_rep = new HistoryRepository();
    }

    public function all(Request $request)
    {
        $params = $request->only(['include', 'filter', 'sort', 'fields', 'page']);
        $key    = md5('history_' . json_encode($params));

        $data = Cache::remember($key, env('CACHE_DURATION', 0), function () use ($params) {
            return $this->_rep->get($params);
        });

        return $this->response($data);
    }

    public function detail(Request $request, $historyId)
    {
        $key    = md5('history_' . $historyId);

        $data = Cache::remember($key, env('CACHE_DURATION', 0), function () use ($historyId) {
            return $this->_rep->getOne($historyId, []);
        });

        return $this->response($data);
    }
}
