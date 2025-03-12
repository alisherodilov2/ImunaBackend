<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Advertisements\AdvertisementsResource;
use App\Models\Advertisements;
use App\Services\Api\V3\Contracts\AdvertisementsServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdvertisementsController extends Controller
{

    public $modelClass = Advertisements::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


    public function index(Request $request, AdvertisementsServiceInterface $service)
    {
        $res = AdvertisementsResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, AdvertisementsServiceInterface $service)
    {
       
        return $this->success(new AdvertisementsResource($service->add($request)));
    }
    public function update(Request $request, $id, AdvertisementsServiceInterface $service)
    {
        return $this->success(new AdvertisementsResource($service->edit($id, $request)));
    }
 
}
