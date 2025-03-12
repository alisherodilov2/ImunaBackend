<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Services\Api\V3\Contracts\RepotServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class RepotController extends Controller
{


    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->repot($request));
        return $this->success($res);
    }
    public function excelRepot(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->excelRepot($request));
        return $this->success($res);
    }
    public function doctor(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->doctor($request));
        return $this->success($res);
    }
    public function counterparty(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->counterparty($request));
        return $this->success($res);
    }
    public function dailyRepot(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->dailyRepot($request));
        return $this->success($res);
    }
    public function dailyRepotShow($id, RepotServiceInterface $service)
    {
        $res = ($service->dailyRepotShow($id));
        return $this->success($res);
    }
    public function doctorShowService($id, RepotServiceInterface $service)
    {
        $res = ($service->doctorShowService($id));
        return $this->success($res);
    }
    public function doctorShow(Request $request, $id, RepotServiceInterface $service)
    {
        $res = ($service->doctorShow($id, $request));
        return $this->success($res);
    }

    public function dailyRepotUpdate(Request $request, RepotServiceInterface $service)
    {
        $res = ($service->dailyRepotUpdate($request));
        return $this->success($res);
    }
    public function counterpartyShow(Request $request, $id, RepotServiceInterface $service)
    {
        $res = ($service->counterpartyShow($id, $request));
        return $this->success($res);
    }
    public function counterpartyClientShow(Request $request, $id, RepotServiceInterface $service)
    {
        $res = ($service->counterpartyClientShow($id, $request));
        return $this->success($res);
    }
}
