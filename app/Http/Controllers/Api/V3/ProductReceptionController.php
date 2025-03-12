<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReception\ProductReceptionResource;
use App\Models\ProductReception;
use App\Services\Api\V3\Contracts\ProductReceptionServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductReceptionController extends Controller
{

    public $modelClass = ProductReception::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ProductReceptionServiceInterface $service)
    {
        $res = ProductReceptionResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, ProductReceptionServiceInterface $service)
    {
        if (isset($request->re_send)) {
            return $this->success(($service->add($request)));
        }
        return $this->success(new ProductReceptionResource($service->add($request)));
    }
    public function update(Request $request, $id, ProductReceptionServiceInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
    public function show(Request $request, $id, ProductReceptionServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
    public function itemDelete($id, $parentId, ProductReceptionServiceInterface $service)
    {
        return $this->success(($service->itemDelete($id, $parentId)));
    }
}
