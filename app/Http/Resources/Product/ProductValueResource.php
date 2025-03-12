<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductValueResource extends JsonResource
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
            'product_type' => $this->product_type,
            'expires_date' => $this->expires_date,
            'count' => (int)$this->count,
            'old_price' => (int)$this->old_price,
            'product_sell_type' => $this->product_sell_type,
            'price_sell' => (int)$this->price_sell,
            'branch_id' => $this->branch_id,
            'branch' => $this->branchs,
            'current_price' => (int)$this->current_price,
        ];
    }
}
