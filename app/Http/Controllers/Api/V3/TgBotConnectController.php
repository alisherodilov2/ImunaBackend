<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\TgBotConnect\TgBotConnectResource;
use App\Models\TgBotConnect;
use App\Services\Api\V3\Contracts\TgBotConnectServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class TgBotConnectController extends Controller
{

    public $modelClass = TgBotConnect::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, TgBotConnectServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function tgConnect(Request $request, TgBotConnectServiceInterface $service)
    {

        return $this->success(($service->tgConnect($request)));
    }
    public function storeExcel(Request $request, TgBotConnectServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }

    public function update(Request $request, $id, TgBotConnectServiceInterface $service)
    {
        return $this->success($service->update($id, $request));
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
