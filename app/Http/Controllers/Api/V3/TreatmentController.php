<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Treatment\TreatmentResource;
use App\Models\Treatment;
use App\Services\Api\V3\Contracts\TreatmentServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TreatmentController extends Controller
{

    public $modelClass = Treatment::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


    public function index(Request $request, TreatmentServiceInterface $service)
    {
        $res = TreatmentResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, TreatmentServiceInterface $service)
    {
       
        return $this->success(new TreatmentResource($service->add($request)));
    }
    public function update(Request $request, $id, TreatmentServiceInterface $service)
    {
        return $this->success(new TreatmentResource($service->edit($id, $request)));
    }
 
}
