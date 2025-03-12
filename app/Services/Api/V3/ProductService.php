<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Product\ProductRepotResource;
use App\Http\Resources\Product\ProductRepotShowResource;
use App\Models\Client;
use App\Models\ClientUseProduct;
use App\Models\Graph;
use App\Models\GraphArchive;
use App\Models\Product;
use App\Models\ProductReceptionItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\ProductServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductService implements ProductServiceInterface
{
    public $modelClass = Product::class;
    use Crud;
    public function filter($request)
    {
        return $this->modelClass::
            // where('user_id', auth()->user()->owner_id)
            //     ->
            where(function ($q) use ($request) {
                if (isset($request->is_material)) {
                    $q->whereHas('prodcutCategory', function ($q) {
                        $q->where('is_material', 1);
                    });
                }
                if (auth()->user()->role === User::USER_ROLE_RECEPTION || auth()->user()->role === User::USER_ROLE_PHARMACY) {
                    if (auth()->user()->role === User::USER_ROLE_PHARMACY) {
                        $q
                            ->whereHas('prodcutCategory', function ($q) {
                                $q->where('is_material', 1);
                            })
                            ->whereIn('user_id', [
                                auth()->user()->owner_id,
                                ...User::where('owner_id', auth()->user()->owner_id)->pluck('id')
                            ]);
                    } else {
                        $q
                            ->where('user_id', auth()->user()->owner_id)
                            ->orWhere('user_id', auth()->user()->id);
                    }
                } else {
                    $q->where('user_id', auth()->user()->id)
                        ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                    ;
                }
            })
            ->with(['prodcutCategory' => function ($q) use ($request) {

                if (auth()->user()->role === User::USER_ROLE_PHARMACY) {
                    $q->whereIn('user_id', [
                        auth()->user()->owner_id,
                        ...User::where('owner_id', auth()->user()->owner_id)->pluck('id')
                    ]);
                }
                if (isset($request->is_material)) {
                    $q->whereHas('prodcutCategory', function ($q) {
                        $q->where('is_material', 1);
                    });
                }
            }, 'productReceptionItem' => function ($q) use ($request) {
                if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
                    $q->with(['productReception' => function ($q) use ($request) {
                        $q->whereIn(
                            'user_id',
                            User::where('owner_id', auth()->user()->owner_id)->orWhere('id', auth()->user()->id)->pluck('id')
                        );
                    }]);
                }
            }])
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        return $this->modelClass::where('id', $result->id)->with(['prodcutCategory', 'productReceptionItem'])->first();
    }
    public function edit($id, $request)
    {
        $request = $request;
        $result = $this->update($id, $request);
        return $this->modelClass::where('id', $result->id)->with(['prodcutCategory', 'productReceptionItem'])->first();
    }

    public function show($id, $request)
    {
        $today = Carbon::today();
        // $alert_dedline_day = $this->alert_dedline_day;

        // // $fiveDaysLater = $today->copy()->addDays($this->expiration_day);
        // $danger_qty =  $productReceptionItem->filter(function ($item) use ($today,$alert_dedline_day ) {
        //     $daysRemaining = $today->diffInDays(Carbon::parse($item->expiration_date), false);

        //     // Faqat 5 kundan kichik yoki teng bo'lganlarini qaytaramiz
        //     return $daysRemaining >= 0 && $daysRemaining <= $alert_dedline_day;
        // })->sum('qty');
        $product = $this->modelClass::find($id);
        // return $this->modelClass::with(['productReceptionItem' => function ($q) use ($id,$today) {
        //     $q->where(function ($q) use ($id,$today) {
        //         $fiveDaysLater = $today->copy()->subDays($this->alert_dedline_day);
        //     });
        //     $q->with(['prodcut', 'prodcutCategory']);
        // }])->find($id);
        return ProductReceptionItem::where('product_id', $id)
            ->whereHas('productReception', function ($q) use ($id) {
                if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
                    $q

                        ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
                } else {
                    $q

                        ->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                }
            })
            ->where(function ($q) use ($id, $today, $product, $request) {
                if (isset($request->status) && $request->status == 'danger') {
                    // $fiveDaysLater = $today->copy()->subDays($product->alert_dedline_day);
                    // $q->where('expiration_date', '<=', $fiveDaysLater);
                    $q->whereRaw('DATEDIFF(expiration_date, ?) <= ?', [$today, $product->alert_dedline_day]);
                }
            })
            ->with('pharmacyProduct')
            ->with(['prodcut', 'prodcutCategory'])->get();
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
        $clientUseProducts = ClientUseProduct::where(function ($q) use ($startDate, $endDate) {



            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q
                    ->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q
                    ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
            }
        })
            ->whereHas('client', function ($q) {
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
            ->selectRaw('
        DATE(created_at) as date,
         GROUP_CONCAT( CONCAT("[", product_reception_item_id, ",", qty, ",", client_id, "]")) AS product_reception_item_ids,
        GROUP_CONCAT(DISTINCT product_id) as product_ids,
        COUNT(*) as count
    ')
            ->groupBy('date')
            ->get();
        return [
            'data' => ProductRepotResource::collection($clientUseProducts),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    public function repotShow($request)
    {

        $clientUseProducts = ClientUseProduct::where(function ($q) use ($request) {
            $q->whereDate('created_at', $request->date);
        })
            ->selectRaw('
        client_id, 
        product_id, 
        GROUP_CONCAT( CONCAT("[", product_reception_item_id, ",", qty, ",", product_id, "]")) AS product_reception_item_ids
    ')

            ->groupBy('client_id', 'product_id')
            ->with(['client:id,first_name,last_name,phone', 'product.prodcutCategory'])  // Agar siz client relatsiyasini olishni istasangiz
            ->get();
        return ProductRepotShowResource::collection($clientUseProducts);
    }

    // uyda, muolajda hisobot
    public function reportProductAmbulatorAndTreatment($request)
    {
        $product = collect([]);

        // Fetching data for "at home" usage
        $grahAchiveAtHome = GraphArchive::where('status', 'live')
            ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
            ->where('use_status', 'at_home')
            ->whereHas('graphArchiveItem', function ($q) {
                $q->where('is_assigned', 0)
                    ->orWhereNull('is_assigned');
            })
            ->withCount(['graphArchiveItem as unassigned_count' => function ($q) {
                $q->where('is_assigned', 0)
                    ->orWhereNull('is_assigned');
            }])
            ->with(['treatment.treatmentServiceItem.service.serviceProduct.product'])
            ->get();

        // Fetching data for "treatment" usage
        $grahAchive = GraphArchive::where('status', 'live')
            ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
            ->where('use_status', 'treatment')
            ->whereHas('graphArchiveItem', function ($q) {
                $q->where('client_id',0)
                ->orwhereNull('client_id');
            })
            ->withCount(['graphArchiveItem as unassigned_count' => function ($q) {
                $q->where('client_id',0)
                ->orwhereNull('client_id');
            }])
            ->with(['treatment.treatmentServiceItem.service.serviceProduct.product'])
            ->get();

        // Processing "at home" items
        foreach ($grahAchiveAtHome as $item) {
            $treatmentServiceItems = $item->treatment->treatmentServiceItem;

            foreach ($treatmentServiceItems as $treatmentServiceItemParent) {
                $serviceProducts = $treatmentServiceItemParent->service->serviceProduct;

                if ($serviceProducts && $serviceProducts->count() > 0) {
                    $graphArchiveItemCount = $item->unassigned_count;

                    foreach ($serviceProducts as $childItem) {
                        $serviceQty = $childItem->qty;
                        $existingProduct = $product->firstWhere('id', $childItem->product->id);

                        if ($existingProduct) {
                            // Update the existing product count
                            $product = $product->map(function ($productItem) use ($existingProduct, $graphArchiveItemCount, $serviceQty) {
                                if ($productItem['id'] === $existingProduct['id']) {
                                    $productItem['at_home_count'] += $graphArchiveItemCount * $serviceQty;
                                }
                                return $productItem;
                            });
                        } else {
                            // Add a new product to the collection
                            $product->push([
                                'id' => $childItem->product->id,
                                'name' => $childItem->product->name,
                                'at_home_count' => $graphArchiveItemCount * $serviceQty,
                                'treatment_count' => 0
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($grahAchive as $item) {
            $treatmentServiceItems = $item->treatment->treatmentServiceItem;

            foreach ($treatmentServiceItems as $treatmentServiceItemParent) {
                $serviceProducts = $treatmentServiceItemParent->service->serviceProduct;

                if ($serviceProducts && $serviceProducts->isNotEmpty()) {
                    $graphArchiveItemCount = $item->unassigned_count;

                    foreach ($serviceProducts as $childItem) {
                        $serviceQty = $childItem->qty;
                        $existingProduct = $product->firstWhere('id', $childItem->product->id);

                        if ($existingProduct) {
                            // Update the treatment count for the existing product
                            $product = $product->map(function ($productItem) use ($existingProduct, $graphArchiveItemCount, $serviceQty) {
                                if ($productItem['id'] === $existingProduct['id']) {
                                    $productItem['treatment_count'] = ($productItem['treatment_count'] ?? 0) + $graphArchiveItemCount * $serviceQty;
                                }
                                return $productItem;
                            });
                        } else {
                            // Add a new product to the collection with treatment_count
                            $product->push([
                                'id' => $childItem->product->id,
                                'name' => $childItem->product->name,
                                'treatment_count' => $graphArchiveItemCount * $serviceQty,
                                'at_home_count' => 0
                            ]);
                        }
                    }
                }
            }
        }


        $product = $product->map(function ($productItem) {
            $productReceptionItem = ProductReceptionItem::where('product_id', $productItem['id'])
                ->whereHas('productReception', function ($q) {
                    $q->whereIn(
                        'user_id',
                        User::where('owner_id', auth()->user()->owner_id)->orWhere('id', auth()->user()->id)->pluck('id')
                    );
                })
                // ->where('expiration_date', '>=', now()->format('Y-m-d'))
                ->whereRaw('qty - IFNULL(use_qty, 0) > 0');
            // ->sum(DB::raw('qty - IFNULL(use_qty, 0)'));
            $productItem['product_count'] = $productReceptionItem->sum(DB::raw('qty - IFNULL(use_qty, 0)'));
            $productItem['expiration_count'] = $productReceptionItem->where('expiration_date', '<', now()->format('Y-m-d'))->sum(DB::raw('qty - IFNULL(use_qty, 0)'));
            return $productItem;
        });

        return [
            'data' => $product,
            'at' => $grahAchiveAtHome,
            'at3' => $grahAchive
        ];
    }
}
