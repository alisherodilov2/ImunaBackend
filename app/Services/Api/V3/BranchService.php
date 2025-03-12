<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Product\ProductResource;
use App\Models\Branch;
use App\Models\BranchItem;
use App\Models\BranchServiceItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Api\V3\Contracts\BranchServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class BranchService implements BranchServiceInterface
{
    public $modelClass = Branch::class;
    use Crud;
    public function filter()
    {

        return $this->modelClass::with(['branchItems.targetBranch', 'mainBranch'])->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        $reqDdata = collect(json_decode($request->branch_id));
        foreach ($reqDdata as $key => $value) {
            BranchItem::create([
                'branch_id' => $result->id,
                'target_branch_id' => $value
            ]);
        }
        return $this->modelClass::with(['branchItems.targetBranch', 'mainBranch'])->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        $reqDdata = collect(json_decode($request->branch_id));
        BranchItem::where('branch_id', $result->id)
            ->whereNotIn('target_branch_id', $reqDdata)
            ->delete();
        foreach ($reqDdata as $key => $value) {
            if (!BranchItem::where('branch_id', $result->id)->where('target_branch_id', $value)->exists()) {
                BranchItem::create([
                    'branch_id' => $result->id,
                    'target_branch_id' => $value
                ]);
            }
        }

        return $this->modelClass::with(['branchItems.targetBranch', 'mainBranch'])->find($result->id);
    }

    // filallar qoldigi

    public function remainingBranches($request)
    {
        $user = auth()->user();
        $branchId = $user->owner_id;
        if (isset($request->branch_id) && $request->branch_id > 0) {
            $branchId = $request->branch_id;
        }

        $branch = Branch::where('main_branch_id', $user->owner_id)
            ->with(['branchItems' => function ($q) use ($branchId) {
                $q->with('targetBranch:id,name');
            }])
            ->first();
        $mainBranch = User::where('id', $user->owner_id)->first();

        $data =  Product::where(function ($q) use ($request, $user) {

            $q->where('user_id', $user->owner_id)
                ->orWhereIn('user_id', User::where('owner_id', $user->owner_id)->pluck('id'))
            ;
        })
            // ->whereHas('productReceptionItem', function ($q) use ($request, $branchId) {
            //     $q->whereHas('productReception', function ($q) use ($request, $branchId) {
            //         $q->whereIn('user_id', User::where('owner_id', $branchId)->pluck('id'));
            //     });
            // })
            ->with(['prodcutCategory' => function ($q) use ($request) {}, 'productReceptionItem' => function ($q) use ($request, $branchId) {
                $q->with(['productReception' => function ($q) use ($request, $branchId) {
                    $q->whereIn('user_id', User::where('owner_id', $branchId)->pluck('id'));
                }]);
            }])
            ->get();

        return [
            'data' => ProductResource::collection($data),
            'branch' => $branch,
            'main_branch' => [
                'name' => $mainBranch->name,
                'id' => $mainBranch->id
            ],
            'branch_id' => $branchId
        ];
    }
}
