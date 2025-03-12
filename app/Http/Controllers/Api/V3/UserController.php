<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Klinka\KlinkaRequest;
use App\Http\Resources\User\UserResource;
use App\Models\Departments;
use App\Models\User;
use App\Services\Api\V3\Contracts\KlinkaServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public $modelClass = User::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, KlinkaServiceInterface $service)
    {
        $res = UserResource::collection($service->filter(User::USERS_ROLE));
        return $this->success($res);
    }
    public function store(KlinkaRequest $request, KlinkaServiceInterface $service)
    {
        $request->validated();
        $check = $this->modelClass::where(['role' => User::USER_ROLE_DIRECTOR])->first();
        if ($check) {
            $reqPassword  = '1111';
            if (isset($request->password)) {
                $reqPassword = $request->password;
            }
            $passwordCheck = Hash::check($reqPassword, $check->password) ?? false;
            if ($passwordCheck) {
                return $this->error([
                    'message' => 'Пользователь с таким именем уже существует',
                ], 422);
            }
        }
        return $this->success(new UserResource($service->add($request, $request->role)));
    }
    public function update(Request $request, $id, KlinkaServiceInterface $service)
    {
        $request = $request;
        if (isset($request->password)) {
            $check = $this->modelClass::where(['role' => User::USER_ROLE_DIRECTOR])
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
        return $this->success(new UserResource($service->edit($id, $request)));
    }
    public function delete($id)
    {
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->modelClass::whereIn('parent_id', $idAll)->delete();
            return $this->success([
                'data' => ($idAll),
            ]);
        }
        Departments::where('parent_id', $id)->delete();
        $this->modelClass::destroy($id);
        return $this->success([
            'data' => ($id),
        ]);
    }
}
