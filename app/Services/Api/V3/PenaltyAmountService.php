<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Master\MasterResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Master;
use App\Models\Order;
use App\Models\PenaltyAmount;
use App\Services\Api\V3\Contracts\PenaltyAmountServiceInterface;
use App\Traits\Crud;

class PenaltyAmountService implements PenaltyAmountServiceInterface
{
    public $modelClass = PenaltyAmount::class;

    use Crud;
    public function filter()
    {
        return $this->modelClass::all();
    }

    public function add($request)
    {
        $request = $request;

        $result = $this->store($request);

        return [
            'data' => $this->modelClass::with('master')->find($result->id),
            'order' => new OrderResource(Order::find($result->order_id)),
            'master' => new MasterResource(Master::find($result->master_id)),
        ];
    }
    public function edit($id, $request)
    {
        $result = $this->update($id, $request);
        return [
            'data' => $result,
            'order' => new OrderResource(Order::find($result->order_id)),
            'master' => new MasterResource(Master::find($result->master_id)),
        ];
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        $masterId = $result->master_id;
        $result->delete();
        $order =  OrderResource::collection(Order::where(['master_id' => $masterId, 'is_freeze' => 0])->get());
        $master = new MasterResource(Master::with('order')->find($masterId));
        return [
            'data' => $id,
            'order' => $order,
            'master' => $master,
        ];
    }

    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                PenaltyAmount::updateOrCreate(
                    [
                        'name' => $item?->name,
                    ], [
                        'name' => $item?->name,
                        'address' => $item?->name,
                        'photo' => $item?->photo,
                    ]);
            }
        }
        return $this->modelClass::all();
    }

}
