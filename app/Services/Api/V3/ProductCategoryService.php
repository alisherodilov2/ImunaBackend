<?php

namespace App\Services\Api\V3;

use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Api\V3\Contracts\ProductCategoryServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class ProductCategoryService implements ProductCategoryServiceInterface
{
    public $modelClass = ProductCategory::class;
    use Crud;
    public function filter()
    {
        // if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
        //     return $this->modelClass::Where('user_id', auth()->id())
        //         ->get();
        // }
        return $this->modelClass::where(function ($q) {
            if (auth()->user()->role === User::USER_ROLE_RECEPTION) {
                $q->where('user_id', auth()->user()->owner_id)
                    ->orWhere('user_id', auth()->user()->id)
                ;
            }
            if (auth()->user()->role === User::USER_ROLE_PHARMACY) {
                $q->where('is_material', 1)->whereIn('user_id', [
                    auth()->user()->owner_id,
                    ...User::where('owner_id', auth()->user()->owner_id)->pluck('id')
                ]);
            } else {
                $q->where('user_id', auth()->user()->id)
                    ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                ;
            }
        })
            ->get();
        // return $this->modelClass::where('user_id', auth()->user()->id)
        //     ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        return $result;
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $result;
    }
}
