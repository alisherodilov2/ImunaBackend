<?php

namespace App\Http\Resources\Product;

use App\Models\Product;
use App\Models\ProductReceptionItem;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRepotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $product_reception_item_ids = json_decode("[" . $this->product_reception_item_ids . "]");
        $product_ids  = json_decode("[" . $this->product_ids . "]");

        return [
            'date' => $this->date,
            'product_reception_item_isds' => $this->product_reception_item_ids,
            'product_category_count' => Product::whereIn('id', $product_ids)->pluck('product_category_id')->unique()->count(),
            'product_qty'=> array_reduce($product_reception_item_ids, function ($carry, $item)  {
                return $carry + ($item[1]);
            }, 0),
            'total_price' => ProductReceptionItem::whereIn('id', array_map(fn($item) => $item[0], $product_reception_item_ids))->get()->sum(function ($q) use ($product_reception_item_ids) {
                return $q->price * array_reduce($product_reception_item_ids, function ($carry, $item) use ($q) {
                    return $carry + ($item[0] === $q->id ? $item[1] : 0);
                }, 0);
            }),
        ];
    }
}
