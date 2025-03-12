<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\PharmacyProduct\PharmacyProductResource;
use App\Models\PharmacyProduct;
use App\Services\Api\V3\Contracts\PharmacyProductServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PharmacyProductController extends Controller
{

    public $modelClass = PharmacyProduct::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, PharmacyProductServiceInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function qrCodeScan(Request $request, PharmacyProductServiceInterface $service)
    {
        $res = ($service->qrCodeScan($request));
        return $this->success($res);
    }
    public function store(Request $request, PharmacyProductServiceInterface $service)
    {
        if (isset($request->re_send)) {
            return $this->success(($service->add($request)));
        }
        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, PharmacyProductServiceInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
    public function show(Request $request, $id, PharmacyProductServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
}
