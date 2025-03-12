<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'qty' => $this->qty,
            'price' => $this->price,
            'master_id' => $this->master_id,
            'master_salary' => $this->master_salary,
            'address' => $this->address,
            'is_check' => $this->is_check,
            'is_freeze' => $this->is_freeze,
            'finish_date' => $this->finish_date,
            'operator' => $this->operator,
            'master' => $this->master,
            'is_finish' => $this->is_finish,
            'is_installation_time' => $this->is_installation_time,
            'master_salary_pay' => $this->master_salary_pay,
            'status' => $this->status,
            'comment' => $this->comment,
            'warranty_period_quantity' => $this->warranty_period_quantity,
            'warranty_period_type' => $this->warranty_period_type,
            'warranty_period_date' => $this->warranty_period_date,
            'customer_id' => $this->customer_id,
            'target_adress' => $this->target_adress,
            'penalty_amount' => $this->penaltyAmount->sum('price'),
            'installation_time' => $this->installation_time,
        ];
    }
}
