<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Master\MasterResource;
use App\Models\Master;
use App\Services\Api\V3\Contracts\MasterServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class MasterController extends Controller
{

    public $modelClass = Master::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, MasterServiceInterface $service)
    {
        $res =MasterResource::collection($service->filter());
        return $this->success($res);
    }
    public function orderShow(Request $request, MasterServiceInterface $service)
    {
        $res =($service->orderShow($request));
        return $this->success($res);
    }
    public function store(Request $request, MasterServiceInterface $service)
    {

        return $this->success(new MasterResource($service->store($request)));
    }
    public function storeExcel(Request $request, MasterServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }

    public function update(Request $request, $id, MasterServiceInterface $service)
    {
        return $this->success(new MasterResource($service->edit($id, $request)));
    }
    public function masterPay($id, MasterServiceInterface $service)
    {
        return $this->success(($service->masterPay($id)));
    }
    public function delete($id)
    {
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->modelClass::whereIn('parent_id', $idAll)->delete();
            return $this->success($idAll);
        }

        $this->modelClass::destroy($id);
        return $this->success($id);

    }

}
