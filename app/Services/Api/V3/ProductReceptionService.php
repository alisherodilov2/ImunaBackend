<?php

namespace App\Services\Api\V3;

use App\Models\Product;
use App\Models\ProductReception;
use App\Models\ProductReceptionItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\ProductReceptionServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProductReceptionService implements ProductReceptionServiceInterface
{
    public $modelClass = ProductReception::class;
    use Crud;
    public function filter()
    {

        return $this->modelClass::where(function ($q) {
            if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
                $q->where('user_id', auth()->user()->owner_id)
                    ->orWhere('user_id', auth()->user()->id)
                ;
            } else {
                $q->where('user_id', auth()->user()->id)
                    ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                ;
            }
        })
            ->with('productReceptionItem')
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $date = now()->format('Y-m-d');
        if (isset($request->product_reception_id)) {
            $result =  $this->modelClass::find($request->product_reception_id);
        } else
        if ($this->modelClass::whereDate('created_at', $date)->where('user_id', $id)->exists()) {
            $result =  $this->modelClass::whereDate('created_at', $date)->where('user_id', $id)->first();
        } else {
            $result = $this->store($request);
        }
        Log::info('result', [$result]);
        $this->modelClass = ProductReceptionItem::class;
        $request['product_reception_id'] = $result->id;
        $res = $this->store($request);
        Product::find($request->product_id)->update(['price' => $request->price]);
        if (isset($request->re_send)) {
            return ProductReception::with(['productReceptionItem' => function ($q) use ($id) {
                $q->with(['prodcut', 'prodcutCategory']);
            }])
                ->find($result->id);
        }
        return $result;
    }
    public function edit($id, $request)
    {

        $this->modelClass = ProductReceptionItem::class;
        $this->update($id, $request);
        Product::find($request->product_id)->update(['price' => $request->price]);
        return ProductReception::with(['productReceptionItem' => function ($q) use ($id) {
            $q->with(['prodcut', 'prodcutCategory']);
        }])
            ->find($request->product_reception_id);
    }
    public function show($id, $request)
    {

        return $this->modelClass::with(['productReceptionItem' => function ($q) use ($id) {
            $q->with(['prodcut', 'prodcutCategory']);
        }])->find($id);
    }
    public function itemDelete($id, $parentId)
    {

        $idAll = json_decode($id);
        if (is_array($idAll)) {
            ProductReceptionItem::whereIn('id', $idAll)->delete();
        }
        return $this->modelClass::with(['productReceptionItem' => function ($q) use ($id) {
            $q->with(['prodcut', 'prodcutCategory']);
        }])->find($parentId);
    }
}
