<?php

namespace App\Http\Controllers;

use App\Repositories\ChecklistRepository;
use Cache;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    private $_rep;

    public function __construct()
    {
        parent::__construct();
        $this->_rep = new ChecklistRepository();
    }

    public function all(Request $request)
    {
        $params = $request->only(['include', 'filter', 'sort', 'fields', 'page']);
        $key    = md5('checklist_' . json_encode($params));

        $data = Cache::remember($key, env('CACHE_DURATION', 0), function () use ($params) {
            return $this->_rep->get($params);
        });

        return $this->response($data);
    }

    public function detail(Request $request, $checklistId)
    {
        $params = $request->only(['include']);
        $key    = md5('checklist_' . $checklistId . '_' . json_encode($params));

        $data = Cache::remember($key, env('CACHE_DURATION', 0), function () use ($params, $checklistId) {
            return $this->_rep->getOne($checklistId, $params);
        });

        return $this->response($data);
    }

    public function delete($checklistId)
    {
        $data = $this->_rep->delete($checklistId);

        return $this->response($data);
    }

    public function create(Request $request)
    {
        $data     = $request->data;
        $response = $this->_rep->create($data);

        return $this->response($response);
    }

    public function update(Request $request, $checklistId)
    {
        $data     = $request->data;
        $response = $this->_rep->update($checklistId, $data);

        return $this->response($response);
    }

    public function items(Request $request, $checklistId)
    {
        $params = ['include' => 'items'];
        $key    = md5('checklist_items_' . $checklistId . '_' . json_encode($params));

        $data = Cache::remember($key, env('CACHE_DURATION', 0), function () use ($params, $checklistId) {
            return $this->_rep->getOne($checklistId, $params);
        });

        return $this->response($data);
    }
}
