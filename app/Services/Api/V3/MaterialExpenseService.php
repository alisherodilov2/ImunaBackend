<?php

namespace App\Services\Api\V3;

use App\Http\Resources\MaterialExpense\MaterialExpenseRepotResource;
use App\Http\Resources\MaterialExpense\MaterialExpenseRepotShowResource;
use App\Models\Branch;
use App\Models\MaterialExpense;
use App\Models\MaterialExpenseItem;
use App\Models\ProductReceptionItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\MaterialExpenseServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MaterialExpenseService implements MaterialExpenseServiceInterface
{
    public $modelClass = MaterialExpense::class;
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
            ->with('product')
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        $this->userProductCount($result);
        return $this->modelClass::with('product')->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        $this->userProductCount($result);
        return $this->modelClass::with('product')->find($result->id);
    }

    public function userProductCount($expoence)
    {
        // $expoence = $this->modelClass::find($id);
        $items = MaterialExpenseItem::where('material_expense_id', $expoence->id)->get();
        //    6>10
        if ($expoence->qty > $items->sum('qty')) {
            $totalExpence = $expoence->qty - $items->sum('qty');
            $productReceptionItem = ProductReceptionItem::where('product_id', $expoence->product_id)
                ->orderBy('expiration_date', 'asc')
                ->whereRaw('qty - IFNULL(use_qty, 0) > 0')
                ->get();
            foreach ($productReceptionItem as $item) {
                // 5>10
                if ($item->qty >= $totalExpence) {
                    $productReceptionItemFind =  ProductReceptionItem::find($item->id);
                    $productReceptionItemFind->update(['use_qty' => $productReceptionItemFind->use_qty + $totalExpence]);
                    MaterialExpenseItem::create([
                        'material_expense_id' => $expoence->id,
                        'product_reception_item_id' => $item->id,
                        'qty' => $totalExpence,
                        'product_id' => $item->product_id
                    ]);
                    $totalExpence = 0;
                    break;
                } else {
                    // 5<10
                    // 
                    $productReceptionItemFind =  ProductReceptionItem::find($item->id);
                    $productReceptionItemFind->update(['use_qty' => $productReceptionItemFind->use_qty + $totalExpence - $item->qty]);
                    MaterialExpenseItem::create([
                        'material_expense_id' => $expoence->id,
                        'product_reception_item_id' => $item->id,
                        'qty' => $totalExpence,
                        'product_id' => $totalExpence - $item->qty
                    ]);
                    $totalExpence = $totalExpence - $item->qty;
                }
            }
        } else if ($expoence->qty < $items->sum('qty')) {
            $totalExpence = $items->sum('qty') - $expoence->qty;

            foreach ($items as $item) {
                if ($item->qty > $totalExpence) {
                    $productReceptionItemFind =  ProductReceptionItem::find($item->product_reception_item_id);
                    $productReceptionItemFind->update(['use_qty' => $productReceptionItemFind->use_qty - $totalExpence]);
                    MaterialExpenseItem::find($item->id)->update(['qty' => $item->qty - $totalExpence]);
                } else {
                    $productReceptionItemFind =  ProductReceptionItem::find($item->product_reception_item_id);
                    $productReceptionItemFind->update(['use_qty' => $productReceptionItemFind->use_qty - $item->qty]);
                    $totalExpence = $totalExpence - $item->qty;
                    MaterialExpenseItem::find($item->id)->delete();
                }
            }
        }
    }
    public function userProductDeleteCount($idAll)
    {
        $expoence = MaterialExpenseItem::whereIn('material_expense_id', $idAll);
        Log::info('expoence', [$expoence->get()]);
        foreach ($expoence->get() as $item) {
            $productReceptionItemFind =  ProductReceptionItem::find($item->product_reception_item_id);
            $productReceptionItemFind->update(['use_qty' => $productReceptionItemFind->use_qty - $item->qty]);
            // MaterialExpenseItem::find($item->id)->delete();
        }
        $expoence->delete();
    }
    public function delete($id)
    {
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->userProductDeleteCount($idAll);
            $this->modelClass::whereIn('id', $idAll)->delete();
            return ($idAll);
        }
        $this->userProductDeleteCount([$id]);
        $this->modelClass::destroy($id);
        return ($id);
    }
    public function repot($request)
    {
        $startDate = now();
        $endDate = now();
        $branch_id = 0;
        $branchAllId = [];
        if (isset($request->branch_id) && ($request->branch_id > 0 || $request->branch_id == 'all')) {
            $branch_id = $request->branch_id;
            if ($branch_id == 'all') {
                $branchData = Branch::where('main_branch_id', auth()->user()->id)->with('branchItems')->first();
                if ($branchData) {
                    $branchAllId[] = $branchData->main_branch_id;
                    $branchAllId = [
                        ...$branchAllId,
                        ...$branchData->branchItems->pluck('target_branch_id')->toArray()
                    ];
                }
            }
        }
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
        $materialExpense = $this->modelClass::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q
                    ->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q
                    ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
            }
            // if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
            //     $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            // } else {
            //     $q->where('user_id', auth()->id());
            // }
            if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
                $q->where('user_id', auth()->user()->owner_id)
                    ->orWhere('user_id', auth()->user()->id)
                ;
            } else {
                if ($branch_id == 'all') {

                    $q->where('user_id', auth()->user()->id)
                        ->orWhereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->where('user_id', auth()->user()->id)
                    ->orWhereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    // $q->where('owner_id', $branch_id);
                } else {
                    $q->where('user_id', auth()->user()->id)
                        ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                    ;
                }
            }
        })
            ->selectRaw('
        DATE(created_at) as date,
           CONCAT("[", GROUP_CONCAT(DISTINCT id ORDER BY id ASC SEPARATOR ","), "]") as ids

    ')
            ->groupBy('date')
            ->get();
        return [
            'data' => MaterialExpenseRepotResource::collection($materialExpense),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    public function repotShow($request)
    {

        $materialExpense = $this->modelClass::whereDate('created_at', $request->date)
            ->where(function ($q) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                } else {
                    $q->where('user_id', auth()->id());
                }
            })
            ->with(['product.prodcutCategory', 'materialExpenseItem.productReceptionItem'])
            ->get();
        return MaterialExpenseRepotShowResource::collection($materialExpense);
    }
}
