<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Servicetype\ServicetypeResource;
use App\Models\Servicetypes;
use App\Services\Api\V3\Contracts\ServicetypeServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class ServicetypeController extends Controller
{

    public $modelClass = Servicetypes::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ServicetypeServiceInterface $service)
    {
        $res = ServicetypeResource::collection($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ServicetypeServiceInterface $service)
    {

        return $this->success(new ServicetypeResource($service->add($request)));
    }
    public function update(Request $request, $id, ServicetypeServiceInterface $service)
    {
        return $this->success(new ServicetypeResource($service->edit($id, $request)));
    }
 
}
