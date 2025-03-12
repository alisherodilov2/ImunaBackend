<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateCategory\TemplateCategoryResource;
use App\Models\TemplateCategory;
use App\Services\Api\V3\Contracts\TemplateCategoryServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TemplateCategoryController extends Controller
{

    public $modelClass = TemplateCategory::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, TemplateCategoryServiceInterface $service)
    {
        $res = TemplateCategoryResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, TemplateCategoryServiceInterface $service)
    {
       
        return $this->success(new TemplateCategoryResource($service->add($request)));
    }
    public function update(Request $request, $id, TemplateCategoryServiceInterface $service)
    {
        return $this->success(new TemplateCategoryResource($service->edit($id, $request)));
    }
 
}
