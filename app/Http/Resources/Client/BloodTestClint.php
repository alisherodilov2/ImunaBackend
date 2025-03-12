<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class BloodTestClint extends JsonResource
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
            'first_name' => $this->first_name,
            'phone' => $this->phone,
            'person_id' => $this->person_id,
            'data_birth' => $this->data_birth,
            'citizenship' => $this->citizenship,
            'sex' => $this->sex,
            'client_item' => $this->clientItem,
            'use_status' => $this->use_status,
            'pass_number' => $this->pass_number,
        ];
    }
}
