<?php

namespace App\Http\Resources\Room;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'number' => $this->number,
            'room_index' => $this->room_index,
            'price' => $this->price,
            'doctor_contribution' => $this->doctor_contribution,
            'nurse_contribution' => $this->nurse_contribution,
            'is_empty' => $this->is_empty,
        ];
    }
}
