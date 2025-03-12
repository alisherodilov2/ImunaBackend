<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\TgGroup;
use App\Services\Api\V3\Contracts\TgGroupServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;

class TgGroupController extends Controller
{

    public $modelClass = TgGroup::class;
   

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, TgGroupServiceInterface $service)
    {
        $res = $service->filter();
        return $this->success($res);
    }
    public function store(Request $request, TgGroupServiceInterface $service)
    {

        return $this->success(($service->store($request)));
    }
    public function storeExcel(Request $request, TgGroupServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }

    public function update(Request $request, $id, TgGroupServiceInterface $service)
    {
//         $request = $request;
//         // $request['is_send'] = 1; 
// return $request->all();
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
    $tgId =  $this->modelClass::find($id)->tg_id;
        $this->modelClass::destroy($id);
        leaveChat($tgId);
        return $this->success([
            'data' => ($id),
        ]);

    }

}
