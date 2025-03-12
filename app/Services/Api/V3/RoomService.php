<?php

namespace App\Services\Api\V3;

use App\Models\Room;
use App\Models\RoomServiceItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\RoomServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class RoomService implements RoomServiceInterface
{
    public $modelClass = Room::class;
    use Crud;
    public function filter()
    {
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::Where('user_id', auth()->id())
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
        return $this->modelClass::find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $this->modelClass::find($result->id);
    }
    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                $room = $this->modelClass::where(['type' => $item?->type, 'number' => $item?->number, 'user_id' => auth()->id(),'room_index' => $item?->room_index])->first();
                if ($room) {
                    $room->update([
                        'type' => $item?->type ?? $room->type,
                        'number' => $item?->number ??  $room->number,
                        'room_index' => $item?->room_index ?? $room->room_index,
                        'nurse_contribution' => $item?->nurse_contribution ?? $room->nurse_contribution,
                        'doctor_contribution' => $item?->doctor_contribution ?? $room->doctor_contribution,
                        'price' => $item?->price ?? $room->price,
                    ]);
                } else {
                    $this->modelClass::create([
                        'type' => $item?->type,
                        'number' => $item?->number ?? 0,
                        'room_index' => $item?->room_index ?? 0,
                        'nurse_contribution' => $item?->nurse_contribution ?? 0,
                        'doctor_contribution' => $item?->doctor_contribution ?? 0,
                        'price' => $item?->price ?? 0,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
        return $this->modelClass::where('user_id', auth()->id())
            ->get();
    }


    // empty room
    public function emptyRoom($request)
    {
        $room = $this->modelClass::where('is_empty', '!=', 1)
        ->orWhereNull('is_empty')
        ->where('user_id', auth()->user()->owner_id)
        ->get();
        return $room;
    }
}
