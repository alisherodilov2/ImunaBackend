<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Master\MasterResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Master;
use App\Models\Order;
use App\Models\PenaltyAmount;
use App\Services\Api\V3\Contracts\MasterServiceInterface;
use App\Traits\Crud;

class MasterService implements MasterServiceInterface
{
    public $modelClass = Master::class;

    use Crud;
    public function filter()
    {
        return $this->modelClass::with(['order','penaltyAmount'])->get();
    }
    public function edit($id, $request)
    {

        $request = $request;
        if($request->is_active){
            $request['status'] = 'active';
        }else{
            $request['status'] = 'is_active';
        }
        $result = $this->update($id, $request);
        masterActive($result->tg_id,$result->is_active);
        return $result;
    }
    public function orderShow($request)
    {
        if(isset($request->customer_id)){
            return [
                'order'=>OrderResource::collection(Order::where(['customer_id' => $request->customer_id, 'is_freeze' => 0])
                ->with('master')
                ->get()),
            ];
        }
        
        return [
            'order'=>OrderResource::collection(Order::where(['master_id' => $request->master_id, 'is_freeze' => 0])->get()),
            'penalty_amount'=>PenaltyAmount::with('master')->where(['master_id' => $request->master_id])->get(),
        ];
    }
    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                Master::updateOrCreate(
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
    public function masterPay($id)
    {
        $order = Order::find($id);
        $order->update([
            'master_salary_pay' => $order->master_salary,
        ]);
        $master = Master::find($order->master_id);
        masterPay($master->tg_id,$order->id);
        return[
            'order' =>new OrderResource ($order),
            'master'=>new MasterResource( $this->modelClass::with(['order','penaltyAmount'])->find($order->master_id))
        ];
    }
}
