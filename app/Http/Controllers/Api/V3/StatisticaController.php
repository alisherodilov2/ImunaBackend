<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Api\V3\Contracts\StatisticaServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class StatisticaController extends Controller
{


    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, StatisticaServiceInterface $service)
    {
        if (auth()->user()->role == User::USER_ROLE_COUNTERPARTY || (isset($request->show_id) && $request->show_id > 0)) {
            $res = ($service->statisticaCounterparty($request));
            return $this->success($res);
        }
        $res = ($service->statistica($request));
        return $this->success($res);
    }
    public function statisticaHome(Request $request, StatisticaServiceInterface $service)
    {
        $res = ($service->statisticaHome($request));
        return $this->success($res);
    }
    public function statisticaCounterparty(Request $request, StatisticaServiceInterface $service)
    {
        $res = ($service->statisticaCounterparty($request));
        return $this->success($res);
    }
}
