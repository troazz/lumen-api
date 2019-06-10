<?php

namespace App\Http\Controllers;

use App\Repositories\ItemRepository;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    private $_rep;

    public function __construct()
    {
        parent::__construct();
        $this->_rep = new ItemRepository();
    }

    public function detail($checklistId, $itemId)
    {
        $response = $this->_rep->getOne($itemId, ['checklistId' => $checklistId]);

        return $this->response($response);
    }

    public function create(Request $request, $checklistId)
    {
        $data     = $request->data;
        $response = $this->_rep->create($checklistId, $data);

        return $this->response($response);
    }

    public function update(Request $request, $checklistId, $itemId)
    {
        $data     = $request->data;
        $response = $this->_rep->update($checklistId, $itemId, $data);

        return $this->response($response);
    }

    public function bulk(Request $request, $checklistId)
    {
        $data     = $request->data;
        $response = $this->_rep->bulk($checklistId, $data);

        return $this->response($response);
    }

    public function delete($checklistId, $itemId)
    {
        $response = $this->_rep->delete($itemId, ['checklistId' => $checklistId]);

        return $this->response($response);
    }

    public function complete(Request $request)
    {
        $data     = $request->all();
        $response = $this->_rep->complete($data);

        return $this->response($response);
    }

    public function incomplete(Request $request)
    {
        $data     = $request->all();
        $response = $this->_rep->incomplete($data);

        return $this->response($response);
    }
}
