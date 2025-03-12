<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialExpense\MaterialExpenseResource;
use App\Models\MaterialExpense;
use App\Services\Api\V3\Contracts\MaterialExpenseServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MaterialExpenseController extends Controller
{

    public $modelClass = MaterialExpense::class;

    use ApiResponse,  HistoryTraid;


    public function index(Request $request, MaterialExpenseServiceInterface $service)
    {
        $res = MaterialExpenseResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, MaterialExpenseServiceInterface $service)
    {

        return $this->success(new MaterialExpenseResource($service->add($request)));
    }
    public function update(Request $request, $id, MaterialExpenseServiceInterface $service)
    {
        return $this->success(new MaterialExpenseResource($service->edit($id, $request)));
    }
    public function delete($id, MaterialExpenseServiceInterface $service)
    {
        return $this->success($service->delete($id));
    }
    public function repot(Request $request, MaterialExpenseServiceInterface $service)
    {
        return $this->success($service->repot($request));
    }
    public function repotShow(Request $request, MaterialExpenseServiceInterface $service)
    {
        return $this->success($service->repotShow($request));
    }
}
