<?php

namespace App\Services\Api\V3;

use App\Http\Resources\ProductOrder\ProductOrderRepotResource;
use App\Http\Resources\ProductOrder\ProductOrderRepotShowResource;
use App\Models\Client;
use App\Models\ClientUseProductOrder;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\ProductOrderItemDone;
use App\Models\ProductOrderReceptionItem;
use App\Models\ProductReception;
use App\Models\ProductReceptionItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\ProductOrderServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductOrderService implements ProductOrderServiceInterface
{
    public $modelClass = ProductOrder::class;
    use Crud;
    public function filter($request)
    {
        return $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
            $q->with(['product', 'productOrderItemDone']);
        }])
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $request['branch_id'] = $id;
        $request['status'] = 'order_placed';
        if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
            $request['branch_id'] = auth()->user()->owner_id;
        }
        $result = $this->store($request);
        if (isset($request->prodcut_order_item)) {
            $reqDdata = json_decode($request->prodcut_order_item);
            foreach ($reqDdata as $key => $value) {
                ProductOrderItem::create([
                    'product_order_id' => $result->id,
                    'product_id' => $value->product_id,
                    'qty' => $value->qty
                ]);
            }
        }
        return $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
            $q->with(['product', 'productOrderItemDone']);
        }])->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        if ($request->status == 'processing') {
            $request['pharmacy_id'] = auth()->id();
        }
        $result = $this->update($id, $request);
        if ($request->status == 'order_accepted') {
            $res =  $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
                $q->with(['product', 'productOrderItemDone']);
            }])->find($result->id);
            Log::info('s', [$res]);
            return  $this->orderAccepted($res);
        }
        if (auth()->user()->role === User::USER_ROLE_PHARMACY) {
            if ($request->status == 'processing') {

                return  $this->autofill($id);
            }

            return $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
                $q->with(['product', 'productOrderItemDone']);
            }])->find($result->id);
        }
        if (isset($request->prodcut_order_item)) {
            $reqDdata = json_decode($request->prodcut_order_item);
            ProductOrderItem::where(['product_order_id' =>  $result->id])
                ->whereNotIn('id', collect($reqDdata)->filter(function ($item) {
                    return isset($item->id) ?  is_int($item->id)  : false; // Ensure the ID is an integer
                })->pluck('id'))
                ->delete();
            foreach ($reqDdata as $key => $value) {
                $find = ProductOrderItem::find($value->id ?? 0);
                if ($find) {
                    $find->update([
                        'qty' => $value->qty,
                        'product_id' => $value->product_id
                    ]);
                } else {
                    ProductOrderItem::create([
                        'product_order_id' => $result->id,
                        'product_id' => $value->product_id,
                        'qty' => $value->qty
                    ]);
                }
            }
        }
        return $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
            $q->with(['product', 'productOrderItemDone']);
        }])->find($result->id);
    }
    // reseptionga otkazish qabul qilish

    public function orderAccepted($data)
    {
        //    $res =  $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
        //     $q->with(['product', 'productOrderItemDone']);
        // }])->find($result->id);
        $user = auth()->user();
        $request['user_id'] = $user->id;
        $date = now()->format('Y-m-d');
        $productReception = ProductReception::whereDate('created_at', $date)->where('user_id', $user->id)->first();
        if (!$productReception) {
            ProductReception::create([
                'user_id' => $user->id
            ]);
        }
        $productReception = ProductReception::whereDate('created_at', $date)->where('user_id', $user->id)->first();
        log::info('productReception', [$productReception]);
        $productOrderItem = $data->productOrderItem;
        foreach ($productOrderItem as $item) {
            $productOrderItemDone = $item->productOrderItemDone;
            foreach ($productOrderItemDone as $itemDone) {
                $product = Product::find($itemDone->product_id);
                $pharmacy_product_id = PharmacyProduct::find($itemDone->pharmacy_product_id);
                // 'product_reception_id',
                // 'product_category_id',
                // 'manufacture_date',
                // 'product_id',
                // 'qty',
                // 'expiration_date',
                // 'price',
                // 'use_qty',
                ProductReceptionItem::create([
                    'product_order_id' => $data->id,
                    'product_order_item_id' => $item->id,
                    'pharmacy_product_id' => $pharmacy_product_id->id,
                    'product_order_item_done_id' => $itemDone->id,
                    'product_reception_id' => $productReception->id,
                    'product_category_id' => $product->product_category_id,
                    'product_id' => $itemDone->product_id,
                    'manufacture_date' => $pharmacy_product_id->created_at->format('Y-m-d'),
                    'expiration_date' => $itemDone->expiration_date,
                    'qty' => $itemDone->qty,
                    'price' => $product->price,
                    'use_qty' => 0
                ]);
            }
        }
        return $data;
        // 'product_reception_id',
        // 'product_category_id',

        // if (isset($request->product_reception_id)) {
        //     $result =  $this->modelClass::find($request->product_reception_id);
        // } else
        // if ($this->modelClass::whereDate('created_at', $date)->where('user_id', $id)->exists()) {
        //     $result =  $this->modelClass::whereDate('created_at', $date)->where('user_id', $id)->first();
        // } else {
        //     $result = $this->store($request);
        // }
        // Log::info('result', [$result]);
        // $this->modelClass = ProductReceptionItem::class;
        // $request['product_reception_id'] = $result->id;
        // $res = $this->store($request);
        // Product::find($request->product_id)->update(['price' => $request->price]);
        // if (isset($request->re_send)) {
        //     return ProductReception::with(['productReceptionItem' => function ($q) use ($id) {
        //         $q->with(['prodcut', 'prodcutCategory']);
        //     }])
        //         ->find($result->id);
        // }
    }
    public function show($id, $request)
    {
        $today = Carbon::today();
        // $alert_dedline_day = $this->alert_dedline_day;

        // // $fiveDaysLater = $today->copy()->addDays($this->expiration_day);
        // $danger_qty =  $ProductOrderReceptionItem->filter(function ($item) use ($today,$alert_dedline_day ) {
        //     $daysRemaining = $today->diffInDays(Carbon::parse($item->expiration_date), false);

        //     // Faqat 5 kundan kichik yoki teng bo'lganlarini qaytaramiz
        //     return $daysRemaining >= 0 && $daysRemaining <= $alert_dedline_day;
        // })->sum('qty');
        $ProductOrder = $this->modelClass::find($id);
        // return $this->modelClass::with(['ProductOrderReceptionItem' => function ($q) use ($id,$today) {
        //     $q->where(function ($q) use ($id,$today) {
        //         $fiveDaysLater = $today->copy()->subDays($this->alert_dedline_day);
        //     });
        //     $q->with(['prodcut', 'prodcutCategory']);
        // }])->find($id);
        // return ProductOrderReceptionItem::where('ProductOrder_id', $id)
        //     ->where(function ($q) use ($id, $today, $ProductOrder, $request) {
        //         if (isset($request->status) && $request->status == 'danger') {
        //             // $fiveDaysLater = $today->copy()->subDays($ProductOrder->alert_dedline_day);
        //             // $q->where('expiration_date', '<=', $fiveDaysLater);
        //             $q->whereRaw('DATEDIFF(expiration_date, ?) <= ?', [$today, $ProductOrder->alert_dedline_day]);
        //         }
        //     })
        //     ->with(['prodcut', 'prodcutCategory'])->get();
    }

    public function repot($request)
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
        //     $clientUseProductOrders = ClientUseProductOrder::where(function ($q) use ($startDate, $endDate) {

        //         if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
        //             $q->where('user_id', auth()->user()->owner_id)
        //                 ->orWhere('user_id', auth()->user()->id)
        //             ;
        //         } else {
        //             $q->where('user_id', auth()->user()->id)
        //                 ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
        //             ;
        //         }

        //         if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
        //             $q
        //                 ->whereDate('created_at', $endDate->format('Y-m-d'));
        //         } else {
        //             $q
        //                 ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
        //         }
        //     })
        //         ->selectRaw('
        //     DATE(created_at) as date,
        //      GROUP_CONCAT(DISTINCT CONCAT("[", ProductOrder_reception_item_id, ",", qty, ",", client_id, "]")) AS ProductOrder_reception_item_ids,
        //     GROUP_CONCAT(DISTINCT ProductOrder_id) as ProductOrder_ids,
        //     COUNT(*) as count
        // ')
        //         ->groupBy('date')
        //         ->get();
        //     return [
        //         'data' => ($clientUseProductOrders),
        //         'start_date' => $startDate->format('Y-m-d'),
        //         'end_date' => $endDate->format('Y-m-d'),
        //     ];
    }

    public function repotShow($request)
    {

        // $clientUseProductOrders = ClientUseProductOrder::where(function ($q) use ($request) {
        //     $q->whereDate('created_at', $request->date);
        // })
        //     ->selectRaw('
        //         client_id, 
        //         ProductOrder_id, 
        //         GROUP_CONCAT(DISTINCT CONCAT("[", ProductOrder_reception_item_id, ",", qty, "]")) AS ProductOrder_reception_item_ids
        //     ')
        //     ->groupBy('client_id', 'ProductOrder_id')
        //     ->with(['client:id,first_name,last_name,phone', 'ProductOrder.prodcutCategory'])  // Agar siz client relatsiyasini olishni istasangiz
        //     ->get();
        // return ProductOrderRepotShowResource::collection($clientUseProductOrders);
    }


    public function autofill($id)
    {

        $data =  $this->modelClass::with(['branch'])
            ->find($id);
        $productOrderItem = productOrderItem::where('product_order_id', $id)->get();
        $productOrderItemres = [];
        foreach ($productOrderItem as $key => $item) {
            $res = PharmacyProduct::where('product_id', $item->product_id)
                ->where('qty', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->where('expiration_date', '>=', now()->format('Y-m-d'))

                ->get();
            $qty = $item->qty;
            $productOrderItemDone = [];
            foreach ($res as $key => $value) {
                if ($qty >= $value->qty) {
                    $productOrderItemDone[] = [
                        'id' => Str::uuid(),
                        'product_id' => $value->product_id,
                        'qty' => $value->qty,
                        'product_order_item_id' => $item->id,
                        'pharmacy_product_id' => $value->id,
                        'expiration_date' => $value->expiration_date,
                        'created_at' => $value->created_at
                    ];
                    $qty -= $value->qty;
                } else if ($qty < $value->qty && $qty > 0) {
                    $productOrderItemDone[] = [
                        'id' => Str::uuid(),
                        'product_id' => $value->product_id,
                        'qty' => $qty,
                        'product_order_item_id' => $item->id,
                        'pharmacy_product_id' => $value->id,
                        'expiration_date' => $value->expiration_date,
                        'created_at' => $value->created_at
                    ];
                    $qty = 0;
                }
            }
            $productOrderItemres[] = [
                'stoage' => $item->res,
                ...$item->toArray(),
                'product' => $item->product,
                'product_order_item_done' => $productOrderItemDone
            ];
        }

        return [
            ...$data->toArray(),
            'product_order_item' => $productOrderItemres
        ];
    }
    // send-deliver
    public function sendDeliver($id, $request)
    {

        $result =  $this->modelClass::find($id);
        $result->update([
            'status' => 'shipped',
        ]);

        $reqDdata = json_decode($request->product_order_item_done);
        foreach ($reqDdata as $key => $item) {
            ProductOrderItemDone::create([
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'product_order_item_id' => $item->product_order_item_id,
                'pharmacy_product_id' => $item->pharmacy_product_id,
                'expiration_date' => $item->expiration_date
            ]);
            $find =   PharmacyProduct::find($item->pharmacy_product_id);
            $find->update([
                'qty' => $find->qty - $item->qty
            ]);
        }
        // $productOrderItem = productOrderItem::where('product_order_id', $id)->get();
        // $productOrderItemres = [];
        // foreach ($productOrderItem as $key => $item) {
        //     $res = PharmacyProduct::where('product_id', $item->product_id)
        //         ->where('qty', '>', 0)
        //         ->orderBy('expiration_date', 'asc')
        //         ->get();
        //     $qty = $item->qty;
        //     $productOrderItemDone = [];
        //     foreach ($res as $key => $value) {
        //         if ($qty >= $value->qty) {
        //             $productOrderItemDone[] = [
        //                 'id' => Str::uuid(),
        //                 'product_id' => $value->product_id,
        //                 'qty' => $value->qty,
        //                 'product_order_item_id' => $item->id,
        //                 'pharmacy_product_id' => $value->id,
        //                 'expiration_date' => $value->expiration_date,
        //                 'created_at' => $value->created_at
        //             ];
        //             $qty -= $value->qty;
        //         } else if ($qty < $value->qty && $qty > 0) {
        //             $productOrderItemDone[] = [
        //                 'id' => Str::uuid(),
        //                 'product_id' => $value->product_id,
        //                 'qty' => $qty,
        //                 'product_order_item_id' => $item->id,
        //                 'pharmacy_product_id' => $value->id,
        //                 'expiration_date' => $value->expiration_date,
        //                 'created_at' => $value->created_at
        //             ];
        //             $qty = 0;
        //         }
        //     }
        //     $productOrderItemres[] = [
        //         ...$item->toArray(),
        //         'product' => $item->product,
        //         'product_order_item_done' => $productOrderItemDone
        //     ];
        // }

        // return [
        //     ...$data->toArray(),
        //     'product_order_item' => $productOrderItemres
        // ];
        return $this->modelClass::with(['branch', 'productOrderItem' => function ($q) {
            $q->with(['product', 'productOrderItemDone']);
        }])->find($result->id);
    }
}
