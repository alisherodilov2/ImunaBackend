<?php

namespace App\Services\Api\V3;

use App\Models\DoctorTemplate;
use App\Models\User;
use App\Services\Api\V3\Contracts\DoctorTemplateServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class DoctorTemplateService implements DoctorTemplateServiceInterface
{
    public $modelClass = DoctorTemplate::class;
    use Crud;
    public function filter()
    {
        
        return $this->modelClass::where('user_id', auth()->user()->id)
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
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $result;
    }
}
