<?php

namespace App\Http\Resources\doctor;

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
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'room_number' => $this->room_number,
            'room_type' => $this->room_type,
            'name' => $this->name,
            'floor' => $this->floor,
            'main_room' => $this->main_room,
            'letter' => $this->letter,
            'probirka' => $this->probirka,
            'duration' => $this->duration,
            'client' => new ClientValueTimeResource($this->client),
        ];
    }
}
