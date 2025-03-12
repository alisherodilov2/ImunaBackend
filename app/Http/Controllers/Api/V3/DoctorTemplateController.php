<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorTemplate\DoctorTemplateResource;
use App\Models\DoctorTemplate;
use App\Services\Api\V3\Contracts\DoctorTemplateServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorTemplateController extends Controller
{

    public $modelClass = DoctorTemplate::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


    public function index(Request $request, DoctorTemplateServiceInterface $service)
    {
        $res = DoctorTemplateResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, DoctorTemplateServiceInterface $service)
    {
       
        return $this->success(new DoctorTemplateResource($service->add($request)));
    }
    public function update(Request $request, $id, DoctorTemplateServiceInterface $service)
    {
        return $this->success(new DoctorTemplateResource($service->edit($id, $request)));
    }
 
}
