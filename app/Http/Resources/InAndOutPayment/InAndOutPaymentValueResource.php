<?php

namespace App\Http\Resources\InAndOutPayment;

use Illuminate\Http\Resources\Json\JsonResource;

class InAndOutPaymentValueResource extends JsonResource
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
            'user_id' => $this?->user_id,
            'branch_id' => $this?->branch_id,
            'customer_id' => $this?->customer_id,
            'category_id' => $this->category_id,
            'payment' => $this->payment,
            'product_id' => $this->product_id,
            'parent_id' => $this->parent_id,
            'quantity' => $this->quantity,
            'unity_id' => $this->unity_id,
            'out_status' => $this?->out_status,
            'quantity_price' => $this->quantity_price,
            'pay_total' => $this->pay_total,
            'payment_type' => $this->payment_type,
            'date' => $this->date,
            'status' => $this->status,
            'dedline_quantity' => $this->dedline_quantity,
            'dedline_type' => $this->dedline_type,
            'in_and_out_payment_id' => $this->in_and_out_payment_id,
            'initial_price' => $this->initial_price,
            'status' => $this->status,
            'exchange_price' => $this->exchange_price,
            'convert_price' => $this->convert_price,
            'comment' => $this->comment,
            'pay_all_total' => $this->pay_all_total,
        ];
    }
}
