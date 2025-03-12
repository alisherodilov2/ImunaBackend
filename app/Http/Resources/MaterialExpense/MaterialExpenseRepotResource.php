<?php

namespace App\Http\Resources\MaterialExpense;

use App\Models\MaterialExpenseItem;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialExpenseRepotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $materialExpenseItem_id = json_decode($this->ids);
        $materialExpenseItem = MaterialExpenseItem::whereIn('material_expense_id', $materialExpenseItem_id)
            ->with('productReceptionItem')->get();
        return [
            'id' => $this->id,
            'date' => $this->date,
            'qty' => $materialExpenseItem->sum('qty'),
            'total_price' => $materialExpenseItem->sum(function ($item) {
                return $item->productReceptionItem->price * $item->qty;
            })

        ];
    }
}
