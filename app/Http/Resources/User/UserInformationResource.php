<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserInformationResource extends JsonResource
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
            'photo' => $this->photo != null ? 'http://127.0.0.1:8000' . $this->photo : '',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'data_birthday' => $this->data_birthday,
            'phone' => $this->phone,
            'jinsi' => $this->jinsi,
        ];
    }
}
