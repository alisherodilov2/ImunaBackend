<?php

namespace App\Http\Resources\ProductReception;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductReceptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $productReceptionItem = $this->productReceptionItems;
        return [
            'id' => $this->id,
            'date' => $this->created_at->format('Y-m-d'),
            'product_category_count' => $this->productReceptionItem->pluck('product_category_id')->unique()->count(),
            'product_qty' => $this->productReceptionItem->sum('qty'),
            'total_price' => $this->productReceptionItem->sum(function ($item) {
                return $item->price * $item->qty;
            }),
        ];
    }
}
