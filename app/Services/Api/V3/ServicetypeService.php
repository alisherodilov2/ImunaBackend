<?php

namespace App\Services\Api\V3;

use App\Models\Servicetypes;
use App\Services\Api\V3\Contracts\ServicetypeServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class ServicetypeService implements ServicetypeServiceInterface
{
    public $modelClass = Servicetypes::class;
    use Crud;
    public function filter($request)
    {

        if (isset($request->department_id)) {
            return $this->modelClass::where(
                [
                    'department_id' => $request->department_id,
                    'user_id' => auth()->id()
                ]
            )
                ->with('department')
                ->get();
        }
        return $this->modelClass::where('user_id', auth()->id())
            ->with('department')
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        return $this->modelClass::with('department')->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        return $this->modelClass::with('department')->find($result->id);
    }
}
