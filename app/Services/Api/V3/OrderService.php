<?php

namespace App\Services\Api\V3;

use App\Models\Customer;
use App\Models\Master;
use App\Models\Order;
use App\Services\Api\V3\Contracts\OrderServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderService implements OrderServiceInterface
{
    public $modelClass = Order::class;

    use Crud;
    public function filter($request)
    {
        if (isset($request->repot)) {
            return $this->modelClass::with(['master', 'operator'])
                ->where('is_check', 1)
                ->get();
        }
        return $this->modelClass::with(['master', 'operator'])
            ->where('is_check', 0)
            ->get();
    }

    public function add($request)
    {
        $request = $request;
        $request['operator_id'] = auth()->id();
        $request['status'] = 'ether';
        $result = $this->store($request);
        tgGroupSend($result);

        return $result;
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['operator_id'] = auth()->id();

        $result = $this->update($id, $request);
        $master = Master::find($result->master_id);
        $Customer = Customer::find($result->customer_id);
        $Customer->update([
            'address' => $result->address,
            'phone' => $result->phone,
            'target_adress' => $result->target_adress,
        ]);
        $status = $request->type;
        if ($status == 'is_check') {
            // groupOrdercheck($master->tg_id, $result);
            $currentDate = Carbon::now();
            $master->update([
                'status' => 'active',
            ]);
            $request['status'] = 'pay_finally';

            if ($result->warranty_period_type == 'year') {
                $request['warranty_period_date'] = $currentDate->addYear($result->warranty_period_quantity);
            } else {
                $request['warranty_period_date'] = $currentDate->addMonth($result->warranty_period_quantity);
            }
            $result = $this->update($id, $request);
            groupOrdercheck($result);
            return [
                'status' => 'is_check',
                'id' => $result->id,
            ];
        } else
        if ($status == 'is_update') {
            tgGroupSend($result);
        } else
        if ($status == 'is_pay') {
            masterPay($master->tg_id, $result->id);
        }
        if ($status == 'is_freeze') {
            if (+$result->master_id > 0) {
                if (+$result->is_freeze) {
                    $request['status'] = 'master_freeze';
                    $aktiveored = Order::where('master_id', $master->id)
                        ->where('id', '!=', $id)
                        ->whereIn('status', ['do_work', 'finish', 'take_order'])
                        ->first();
                    if ($aktiveored) {
                        return [
                            'message' => 'Aktiv qilish mumkin emas'
                        ];
                    }
                    $master->update([
                        'status' => 'active'
                    ]);
                } else {
                    $aktiveored = Order::where('master_id', $master->id)
                        ->where('id', '!=', $id)
                        ->whereIn('status', ['do_work', 'finish', 'take_order'])
                        ->first();
                    if ($aktiveored) {
                        return [
                            'message' => 'Aktiv qilish mumkin emas'
                        ];
                    }
                    $request['status'] = 'take_order';
                }
            } else {
                if (+$result->is_freeze) {
                    $request['status'] = 'ether_freeze';
                } else {

                    $request['status'] = 'ether';
                }
            }

            $result = $this->update($id, $request);
            freeze($result);
        }
        if ($status == 'is_installation_time') {
            $request['status'] = 'do_work';
            $result = $this->update($id, $request);
            installation_time($this->modelClass::find($result->id));
        }
        if ($status == 'is_reset') {
            masterReset($result);
        }

        if (($status == 'is_edit')) {
            tgGroupSendEdit($this->modelClass::find($result->id));
            
        }


        return $this->modelClass::find($result->id);
    }

    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                Order::updateOrCreate(
                    [
                        'name' => $item?->name,
                    ],
                    [
                        'name' => $item?->name,
                        'address' => $item?->name,
                        'photo' => $item?->photo,
                    ]
                );
            }
        }
        return $this->modelClass::all();
    }
}
