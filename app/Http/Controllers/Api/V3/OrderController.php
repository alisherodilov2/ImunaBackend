<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\Api\V3\Contracts\OrderServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public $modelClass = Order::class;
    public $resource = OrderResource::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, OrderServiceInterface $service)
    {
        $res = $this->resource::collection($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, OrderServiceInterface $service)
    {

        return $this->success(new $this->resource($service->add($request)));
    }
    public function storeExcel(Request $request, OrderServiceInterface $service)
    {
        return $this->success($this->resource::collection($service->storeExcel($request)));
    }

    public function update(Request $request, $id, OrderServiceInterface $service)
    {
        if ($request->type == 'is_check') {
            return $this->success(($service->edit($id, $request)));
        }
        $res = $service->edit($id, $request);
        if(isset($res['message'])){
            return $this->error($res,422);
        }
        return $this->success(new $this->resource($res));
    }
    public function delete($id)
    {
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->modelClass::whereIn('parent_id', $idAll)->delete();
            return $this->success($idAll);
        }
        $res =  $this->modelClass::find($id);
        masterReset($res,false);
        $res->delete();
        return $this->success($id);
    }
}
