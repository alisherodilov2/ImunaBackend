<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOrderBack\ProductOrderBackResource;
use App\Models\ProductOrderBack;
use App\Services\Api\V3\Contracts\ProductOrderBackInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductOrderBackController extends Controller
{

    public $modelClass = ProductOrderBack::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


    public function index(Request $request, ProductOrderBackInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ProductOrderBackInterface $service)
    {

        return (($service->add($request)));
    }
    public function update(Request $request, $id, ProductOrderBackInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
}
