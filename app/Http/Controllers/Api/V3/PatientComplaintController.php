<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\PatientComplaint;
use App\Services\Api\V3\Contracts\PatientComplaintServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class PatientComplaintController extends Controller
{

    public $modelClass = PatientComplaint::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, PatientComplaintServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, PatientComplaintServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, PatientComplaintServiceInterface $service)
    {

        return $this->success(($service->edit($id, $request)));
    }
}
