<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Template\TemplateResource;
use App\Models\Template;
use App\Services\Api\V3\Contracts\TemplateServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TemplateController extends Controller
{

    public $modelClass = Template::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, TemplateServiceInterface $service)
    {
        $res = TemplateResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, TemplateServiceInterface $service)
    {
     
        return $this->success(new TemplateResource($service->add($request)));
    }
    public function update(Request $request, $id, TemplateServiceInterface $service)
    {
        
        return $this->success(new TemplateResource($service->edit($id, $request)));
    }
    
}
