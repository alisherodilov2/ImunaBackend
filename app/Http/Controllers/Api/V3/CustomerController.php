<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Services\Api\V3\Contracts\CustomerServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public $modelClass = Customer::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, CustomerServiceInterface $service)
    {
        $res =CustomerResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, CustomerServiceInterface $service)
    {

        return $this->success(new CustomerResource($service->store($request)));
    }
    public function storeExcel(Request $request, CustomerServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }

    public function update(Request $request, $id, CustomerServiceInterface $service)
    {
        return $this->success(new CustomerResource($service->edit($id, $request)));
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

        $this->modelClass::destroy($id);
        return $this->success([
            'data' => ($id),
        ]);

    }

}
