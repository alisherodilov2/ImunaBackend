<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\PenaltyAmount;
use App\Services\Api\V3\Contracts\PenaltyAmountServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class PenaltyAmountController extends Controller
{

    public $modelClass = PenaltyAmount::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, PenaltyAmountServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, PenaltyAmountServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function storeExcel(Request $request, PenaltyAmountServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }

    public function update(Request $request, $id, PenaltyAmountServiceInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
    public function delete($id, PenaltyAmountServiceInterface $service)
    {
        return $this->success(($service->delete($id)));
    }

}
