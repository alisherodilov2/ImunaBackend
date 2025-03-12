<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOrder\ProductOrderResource;
use App\Models\ProductOrder;
use App\Services\Api\V3\Contracts\ProductOrderServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductOrderController extends Controller
{

    public $modelClass = ProductOrder::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ProductOrderServiceInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function autofill(Request $request, $id, ProductOrderServiceInterface $service)
    {
        $res = ($service->autofill($id));
        return $this->success($res);
    }
    public function sendDeliver(Request $request, $id, ProductOrderServiceInterface $service)
    {
        $res = ($service->sendDeliver($id, $request));
        return $this->success($res);
    }
    public function store(Request $request, ProductOrderServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, ProductOrderServiceInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
    public function show(Request $request, $id, ProductOrderServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
    public function repot(Request $request, ProductOrderServiceInterface $service)
    {
        return $this->success(($service->repot($request)));
    }
    public function repotShow(Request $request, ProductOrderServiceInterface $service)
    {
        return $this->success(($service->repotShow($request)));
    }
 
}
