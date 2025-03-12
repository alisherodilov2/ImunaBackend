<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Resources\Json\JsonResource;

class MasterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $balance = $this->order->sum(function ($order) {
            return $order->master_salary * $order->qty;
        }) - $this->order->sum(function ($order) {
            return $order->master_salary_pay * $order->qty;
        }) ;
        return [
            'id' => $this->id,
            'tg_id' => $this->tg_id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'balance' => $balance,
            'balance_pay' => $this->order->sum(function ($order) {
                return $order->master_salary_pay * $order->qty;
            }),
            'balance_work' => $this->order->sum(function ($order) {
                return $order->master_salary * $order->qty;
            }),
            'order_count' => $this->order->count(),
            'is_contract' => $this->is_contract,
            'is_occupied' => $this->is_occupied,
            'master_id' => $this->master_id,
            'master' => $this->master,
            'is_active' => $this->is_active,
            'penalty_amount' => $this->penaltyAmount->sum('price'),
        ];
    }
}
