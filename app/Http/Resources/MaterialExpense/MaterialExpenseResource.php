<?php

namespace App\Http\Resources\MaterialExpense;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialExpenseResource extends JsonResource
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
        ];
    }
}
