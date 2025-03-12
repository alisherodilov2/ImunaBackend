<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Product\ProductRepotShowResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Models\PharmacyProduct;
use App\Models\PharmacyProductItem;
use App\Models\ServiceProduct;
use App\Models\Services;
use App\Models\User;
use App\Services\Api\V3\Contracts\PharmacyProductServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PharmacyProductService implements PharmacyProductServiceInterface
{
    public $modelClass = PharmacyProduct::class;
    use Crud;
    public function filter($request)
    {

        if (isset($request->show_id) && $request->show_id > 0) {
            return $this->modelClass::where('user_id', auth()->user()->id)
                ->where('product_id', $request->show_id)
                ->with('prodcut')
                ->get();
        }
        return $this->modelClass::where('user_id', auth()->user()->id)->selectRaw('
        product_id,
        sum(qty) as qty
    ')
            ->groupBy('product_id')
            // ->groupBy('date')
            ->get()->map((function ($item) {
                $product = Product::find($item->product_id);
                return [
                    'id' => $item->product_id,
                    'qty' => $item->qty,
                    'product' => [
                        'name' => $product->name,
                    ]
                ];
            }));
        // return $this->modelClass::where('user_id', auth()->user()->id)
        //     ->with('prodcut')
        //     ->get();
    }
    public function qrCodeScan($request)
    {
        try {
            list($pharmacy_product_id, $product_id) = explode("_", $request->code);
            $find = $this->modelClass::find($pharmacy_product_id);
            if ($find) {
                $ser = ServiceProduct::where(['product_id' => $product_id, 'service_id' => $request->service_id])->first();
                if (!$ser) {
                    return  [
                        'message' => 'Bu qr code xizmatka tegishli emas',
                        'error' => true,
                        'product_id' => $product_id
                    ];
                }

                if (Carbon::parse($find->expiration_date)->lt(Carbon::today())) {
                    return  [
                        'message' => 'Mahsulot muddati tugagan',
                        'error' => true
                    ];
                }
                // maxsulot sonini tekshirish kerak
                $product = Product::with(['prodcutCategory', 'productReceptionItem' => function ($q) use ($find) {
                    $q
                        ->where('expiration_date', '>=', now()->format('Y-m-d'))
                        ->whereRaw('qty - IFNULL(use_qty, 0) > 0')
                        ->orderBy('expiration_date', 'asc')
                        // // ->orderBy('created_at', 'desc')
                        // ->where('pharmacy_product_id', $find->id)
                        ->with(['productReception' => function ($q) {
                            $q

                                ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
                        }]);
                }])
                    ->find($find->product_id);
                $client_use_product = [];
                $productReceptionItem = $product->productReceptionItem->filter(function ($item) {
                    return isset($item->productReception->id);
                });
                $totalQty = $request->qty * $ser->qty;
                $product_qty = $request->product_qty;
                
                if ($totalQty < $product_qty) {
                    return  [
                        'message' => 'Kop  olyabsiz',
                        'error' => true
                    ];
                }
                foreach ($productReceptionItem as $item) {
                    if($product_qty==0){
                        break;
                    }
                    if ($totalQty >= $product_qty) {
                        if ($item->qty - $item->use_qty > $product_qty) {
                            $client_use_product[] = [
                                'product_reception_item_id' => $item->id,
                                'qty' => $product_qty,
                                'product_id' => $item->product_id,
                                'service_id' => $request->service_id,
                                'product_reception_item' => [
                                    'manufacture_date' => $item->manufacture_date,
                                    'expiration_date' => $item->expiration_date
                                ]
                            ];
                            $product_qty =  $product_qty - 1;
                        } else {
                            $product_qty =    $product_qty   - $item->qty - $item->use_qty;
                            $client_use_product[] = [
                                'product_reception_item_id' => $item->id,
                                'qty' => $product_qty   - $item->qty - $item->use_qty,
                                'product_id' => $item->product_id,
                                'service_id' => $request->service_id,
                                'product_reception_item' => [
                                    'manufacture_date' => $item->manufacture_date,
                                    'expiration_date' => $item->expiration_date
                                ]
                            ];
                        }
                    }
                }
                if (count($client_use_product) == 0) {
                    return  [
                        'message' => 'Omborda  tugagan',
                        'error' => true
                    ];
                }
                return  [
                    'message' => 'Togri',
                    'client_use_product' => $client_use_product,
                    'product_id' => $find->product_id,
                    'error' => false
                ];
            }
            return [
                'message' => 'Kod xato',
                'error' => true
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'Kod xato',
                'error' => true
            ];
        }
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $res =  $this->store($request);
        if (isset($request->is_home) && $request->is_home == 1) {
            $qty = $this->modelClass::where('product_id', $res->product_id)->where('user_id', $id)->sum('qty');
            return  [
                'id' => $res->product_id,
                'qty' => $qty,
                'product' => [
                    'name' => Product::find($res->product_id)->name,
                ]
            ];
        }

        return $this->modelClass::with('prodcut')->find($res->id);
    }
    public function edit($id, $request)
    {
        $this->update($id, $request);
        return $this->modelClass::with('prodcut')->find($id);
    }
    public function show($id, $request)
    {

        return $this->modelClass::with(['PharmacyProductItem' => function ($q) use ($id) {
            $q->with(['prodcut', 'prodcutCategory']);
        }])->find($id);
    }
}
