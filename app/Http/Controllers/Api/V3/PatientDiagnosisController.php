<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\PatientDiagnosis;
use App\Services\Api\V3\Contracts\PatientDiagnosisServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class PatientDiagnosisController extends Controller
{

    public $modelClass = PatientDiagnosis::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, PatientDiagnosisServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, PatientDiagnosisServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, PatientDiagnosisServiceInterface $service)
    {

        return $this->success(($service->edit($id, $request)));
    }
}
