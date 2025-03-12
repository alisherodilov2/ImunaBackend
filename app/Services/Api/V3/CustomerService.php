<?php

namespace App\Services\Api\V3;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Api\V3\Contracts\CustomerServiceInterface;
use App\Traits\Crud;

class CustomerService implements CustomerServiceInterface
{
    public $modelClass = Customer::class;

    use Crud;
    public function filter()
    {
        return $this->modelClass::with('order')->get();
    }
    public function edit($id, $request)
    {
        $result = $this->update($id, $request);
        Order::where('customer_id', $result->id)
        ->update([
            'full_name' => $result->full_name,
            'phone' => $result->phone,
            'target_adress' => $result->target_adress,
            'address' => $result->address,
        ]);
        return $result;
    }
    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                Customer::updateOrCreate(
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
