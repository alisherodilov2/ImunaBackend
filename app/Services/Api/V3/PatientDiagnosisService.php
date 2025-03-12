<?php

namespace App\Services\Api\V3;

use App\Models\PatientDiagnosis;
use App\Services\Api\V3\Contracts\PatientDiagnosisServiceInterface;
use App\Traits\Crud;

class PatientDiagnosisService implements PatientDiagnosisServiceInterface
{
    public $modelClass = PatientDiagnosis::class;
    use Crud;
    public function filter()
    {
        return $this->modelClass::where('user_id', auth()->id())
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        return $result;
    }
    public function edit($id, $request)
    {
        $request = $request;
        $result = $this->update($id, $request);
        return $result;
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        $result->delete();
        return $result->id;
    }
}
