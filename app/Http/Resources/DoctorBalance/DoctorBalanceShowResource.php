<?php

namespace App\Http\Resources\DoctorBalance;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorBalanceShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return  [
            'id' => $this->id,
            'date' => $this->date,
            'service_count' => $this->service_count,
            'client' => $this->client,
            'total_price' => $this->total_price,
            'total_doctor_contribution_price' => $this->total_doctor_contribution_price,
            'daily_repot'=>$this->dailyRepot
        ];
    }
}
