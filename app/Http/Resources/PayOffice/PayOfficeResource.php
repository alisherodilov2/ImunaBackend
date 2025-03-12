<?php

namespace App\Http\Resources\PayOffice;

use Illuminate\Http\Resources\Json\JsonResource;

class PayOfficeResource extends JsonResource
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
            'type' => $this->type,
            'price' => $this->price,
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
