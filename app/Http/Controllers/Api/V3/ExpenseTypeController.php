<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseType\ExpenseTypeResource;
use App\Models\ExpenseType;
use App\Services\Api\V3\Contracts\ExpenseTypeServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExpenseTypeController extends Controller
{

    public $modelClass = ExpenseType::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ExpenseTypeServiceInterface $service)
    {
        $res = ExpenseTypeResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, ExpenseTypeServiceInterface $service)
    {
       
        return $this->success(new ExpenseTypeResource($service->add($request)));
    }
    public function update(Request $request, $id, ExpenseTypeServiceInterface $service)
    {
        return $this->success(new ExpenseTypeResource($service->edit($id, $request)));
    }
 
}
