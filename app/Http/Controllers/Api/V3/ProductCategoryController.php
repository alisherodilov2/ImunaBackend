<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategory\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Services\Api\V3\Contracts\ProductCategoryServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductCategoryController extends Controller
{

    public $modelClass = ProductCategory::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ProductCategoryServiceInterface $service)
    {
        $res = ProductCategoryResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, ProductCategoryServiceInterface $service)
    {
       
        return $this->success(new ProductCategoryResource($service->add($request)));
    }
    public function update(Request $request, $id, ProductCategoryServiceInterface $service)
    {
        return $this->success(new ProductCategoryResource($service->edit($id, $request)));
    }
 
}
