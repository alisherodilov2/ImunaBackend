<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Department\DepartmentResource;
use App\Models\Departments;
use App\Services\Api\V3\Contracts\DepartmentServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepartmentController extends Controller
{

    public $modelClass = Departments::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, DepartmentServiceInterface $service)
    {
        $res = DepartmentResource::collection($service->filter());
        return $this->success($res);
    }
    public function monitor(Request $request, DepartmentServiceInterface $service)
    {
        $res = ($service->monitor($request));
        return $this->success($res);
    }
    public function store(Request $request, DepartmentServiceInterface $service)
    {
        if (isset($request->password)) {
            $check = $this->modelClass::where(['role' => $request->role])
                ->first();
            if ($check) {
                $passwords = Hash::check($request->password, $check->password) ?? false;
                if ($passwords) {
                    return $this->error([
                        'message' => 'Пользователь с таким именем уже существует',
                    ], 422);
                }
            }
        }
        return $this->success(new DepartmentResource($service->add($request)));
    }
    public function update(Request $request, $id, DepartmentServiceInterface $service)
    {
        if (isset($request->password)) {
            $check = $this->modelClass::where(['role' => $request->role])
                ->where('id', '!=', $id)
                ->first();
            if ($check) {
                $passwords = Hash::check($request->password, $check->password) ?? false;
                if ($passwords) {
                    return $this->error([
                        'message' => 'Пользователь с таким именем уже существует',
                    ], 422);
                }
            }
        }
        return $this->success(new DepartmentResource($service->edit($id, $request)));
    }
    // public function delete($id, DepartmentServiceInterface $service)
    // {
    //     return $this->success([
    //         'data' =>  $service->delete($id),
    //     ]);
    // }
    public function queueNumberLimit($id, DepartmentServiceInterface $service)
    {
        return $this->success($service->queueNumberLimit($id));
    }
}
