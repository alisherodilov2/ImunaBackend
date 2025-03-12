<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Expense\ExpenseResource;
use App\Models\Expense;
use App\Services\Api\V3\Contracts\ExpenseServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExpenseController extends Controller
{

    public $modelClass = Expense::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ExpenseServiceInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ExpenseServiceInterface $service)
    {
       
        return $this->success(new ExpenseResource($service->add($request)));
    }
    public function update(Request $request, $id, ExpenseServiceInterface $service)
    {
        return $this->success(new ExpenseResource($service->edit($id, $request)));
    }
    public function repot(Request $request, ExpenseServiceInterface $service)
    {
        return $this->success($service->repot($request));
    }
    public function repotShow(Request $request, ExpenseServiceInterface $service)
    {
        return $this->success($service->repotShow($request));
    }
}
