<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Room\RoomResource;
use App\Models\Room;
use App\Services\Api\V3\Contracts\RoomServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RoomController extends Controller
{

    public $modelClass = Room::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


    public function index(Request $request, RoomServiceInterface $service)
    {
        $res = RoomResource::collection($service->filter());
        return $this->success($res);
    }
    public function store(Request $request, RoomServiceInterface $service)
    {

        return $this->success(new RoomResource($service->add($request)));
    }
    public function update(Request $request, $id, RoomServiceInterface $service)
    {
        return $this->success(new RoomResource($service->edit($id, $request)));
    }
    public function storeExcel(Request $request, RoomServiceInterface $service)
    {
        return $this->success(RoomResource::collection($service->storeExcel($request)));
    }
    // emptyRoom
    public function emptyRoom(Request $request, RoomServiceInterface $service)
    {
        return $this->success($service->emptyRoom($request));
    }
}
