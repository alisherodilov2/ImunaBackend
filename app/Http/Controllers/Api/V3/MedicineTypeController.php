<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\MedicineType;
use App\Services\Api\V3\Contracts\MedicineTypeServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class MedicineTypeController extends Controller
{

    public $modelClass = MedicineType::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, MedicineTypeServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function itemStore(Request $request, MedicineTypeServiceInterface $service)
    {
        return $this->success($service->itemStore($request));
    }
    public function itemStoreExcel(Request $request, MedicineTypeServiceInterface $service)
    {
        return $this->success($service->itemStoreExcel($request));
    }
    public function itemAll(Request $request, MedicineTypeServiceInterface $service)
    {
        return $this->success($service->itemAll($request));
    }
    public function itemUpdate(Request $request, $id, MedicineTypeServiceInterface $service)
    {
        return $this->success($service->itemUpdate($id, $request));
    }
    public function itemDelete($id, MedicineTypeServiceInterface $service)
    {
        return $this->success($service->itemDelete($id));
    }
    public function store(Request $request, MedicineTypeServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, MedicineTypeServiceInterface $service)
    {

        return $this->success(($service->edit($id, $request)));
    }
}
