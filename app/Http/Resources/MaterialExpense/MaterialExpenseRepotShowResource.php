<?php

namespace App\Http\Resources\MaterialExpense;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialExpenseRepotShowResource extends JsonResource
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
            'product' => $this->product,
            'qty' => $this->qty,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
             'total_price' => $this->materialExpenseItem->sum(function ($item) {
                return $item->productReceptionItem->price * $item->qty;
            })

        ];
    }
}
