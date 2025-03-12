<?php

namespace App\Services\Api\V3;

use App\Models\Advertisements;
use App\Models\User;
use App\Services\Api\V3\Contracts\AdvertisementsServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class AdvertisementsService implements AdvertisementsServiceInterface
{
    public $modelClass = Advertisements::class;
    use Crud;
    public function filter()
    {
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::Where('user_id', auth()->id())
                ->with('client')
                ->get();
        }
        return $this->modelClass::where('user_id', auth()->user()->owner_id)
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        return $this->modelClass::with('client')->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $this->modelClass::with('client')->find($result->id);
    }
}
