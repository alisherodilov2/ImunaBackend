<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'currency' => $this->currency,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'current_price' => $this->current_price,
            'date' => $this->date,
            'in_and_out_payment_value_id' => $this->in_and_out_payment_value_id,
        ];
    }
}
