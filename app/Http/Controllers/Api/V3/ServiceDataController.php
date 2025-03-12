<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceData\ServiceDataResource;
use App\Models\Services;
use App\Services\Api\V3\Contracts\ServiceDataServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class ServiceDataController extends Controller
{

    public $modelClass = Services::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ServiceDataServiceInterface $service)
    {
        $res = ServiceDataResource::collection($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ServiceDataServiceInterface $service)
    {

        return $this->success(new ServiceDataResource($service->add($request)));
    }
    public function update(Request $request, $id, ServiceDataServiceInterface $service)
    {
        return $this->success(new ServiceDataResource($service->edit($id, $request)));
    }
    public function show(Request $request, $id, ServiceDataServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
    public function storeExcel(Request $request, ServiceDataServiceInterface $service)
    {
        return $this->success(ServiceDataResource::collection($service->storeExcel($request)));
    }
    public function showLaboratoryTemplate($id, ServiceDataServiceInterface $service)
    {
        return $this->success($service->showLaboratoryTemplate($id));
    }
    public function laboratoryTemplate(Request $request, $id, ServiceDataServiceInterface $service)
    {
        return $this->success($service->laboratoryTemplate($id,$request));
    }
}
