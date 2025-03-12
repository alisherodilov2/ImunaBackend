<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Product\ProductResource;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\ProductOrderBack;
use App\Models\Treatment;
use App\Models\ProductOrderBackItem;
use App\Models\ProductReception;
use App\Models\ProductReceptionItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\ProductOrderBackInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ProductOrderBackService implements ProductOrderBackInterface
{
    public $modelClass = ProductOrderBack::class;
    use Crud;
    public function filter($request)
    {
        $startDate = now();
        $endDate = now();
        if (isset($request->start_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
                $startDate = $parsedDate;
            }
        }
        if (isset($request->end_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
            if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
                $endDate = $parsedDate;
            }
        }
        $status = ['start'];
        if (isset($request->status)) {
            if ($request->status == 'all') {
                $status = ['start', 'finish'];
            } else {
                $status = [$request->status];
            }
        };
        // if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
        //     return $this->modelClass::Where('user_id', auth()->id())
        //         ->with('ProductOrderBackItem.service')
        //         ->get();
        // }
        return [
            'data' => $this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                ->whereIn('status', $status)
                ->where(function ($q) use ($startDate, $endDate, $request) {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q->whereDate('created_at', $endDate->format('Y-m-d'));
                    } else {
                        $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                    }
                    if (isset($request->product_id) && $request->product_id > 0) {
                        $q->where('product_id', $request->product_id);
                    }
                })
                ->with(['user.owner', 'product'])
                ->get(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $request['product_reception_item_id'] = $request->id;
        $request['status'] = 'start';
        $productReceptionItem = ProductReceptionItem::find($request->id);
        $result = $this->store($request);
        $productReceptionItem->update(['use_qty' => $productReceptionItem->use_qty + $result->qty]);
        return [
            'product' => new ProductResource(Product::with(['prodcutCategory', 'productReceptionItem'])->find($result->product_id)),
            'target' => ProductReceptionItem::with('pharmacyProduct')
                ->with(['prodcut', 'prodcutCategory'])->find($productReceptionItem->id)
        ];
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        if ($result->type == 'return') {
            // pharmacy_product_id
            $PharmacyProduct = PharmacyProduct::find($result->pharmacy_product_id);
            $PharmacyProduct->update(['qty' => $PharmacyProduct->qty + $result->qty]);
        }
        return     $this->modelClass::with(['user.owner', 'product'])->find($result->id);
    }
}
