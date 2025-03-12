<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\Api\V3\Contracts\ProductServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductController extends Controller
{

    public $modelClass = Product::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ProductServiceInterface $service)
    {
        $res = ProductResource::collection($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ProductServiceInterface $service)
    {

        return $this->success(new ProductResource($service->add($request)));
    }
    public function update(Request $request, $id, ProductServiceInterface $service)
    {
        return $this->success(new ProductResource($service->edit($id, $request)));
    }
    public function show(Request $request, $id, ProductServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
    public function repot(Request $request, ProductServiceInterface $service)
    {
        return $this->success(($service->repot($request)));
    }
    public function repotShow(Request $request, ProductServiceInterface $service)
    {
        return $this->success(($service->repotShow($request)));
    }
    public function reportProductAmbulatorAndTreatment(Request $request, ProductServiceInterface $service)
    {
        return $this->success(($service->reportProductAmbulatorAndTreatment($request)));
    }
}
