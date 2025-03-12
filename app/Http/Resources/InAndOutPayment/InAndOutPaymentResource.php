<?php

namespace App\Http\Resources\InAndOutPayment;

use Illuminate\Http\Resources\Json\JsonResource;

class InAndOutPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (isset($this?->id)) {
            return [
                'id' => $this?->id,
                'user_id' => $this?->user_id,
                'branch_id' => $this?->branch_id,
                'customer_id' => $this?->customer_id,
                'status' => $this?->status,
                'in_payment_value' => InAndOutPaymentValueResource::collection($this?->inPaymentValue ?? $this['inAndOutPayment']),
                'status' => $this?->status,
                'save' => true,

            ];
        } else if (isset($this['inAndOutPayment'])) {
            return [
                'customer_id' => $request?->customer_id,
                'user_id' => 1,
                'search' => true,
                'in_payment_value' => InAndOutPaymentValueResource::collection($this['inAndOutPayment']),
            ];
        }
        return [];
    }
}
