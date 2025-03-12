<?php

namespace App\Http\Resources\DoctorBalance;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorBalanceResource extends JsonResource
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
            'name' => $this->name,
            'full_name' => $this->full_name,
            'owner' => $this->owner,
            'phone' => $this->phone,
            'department' => $this->department,
            'service_count' => $this->doctorBalance->sum('service_count'),
            'client_count' => $this->doctorBalance->count(),
            'total_price' => $this->doctorBalance->sum('total_price'),
            'contribution_price' => $this->doctorBalance->sum('total_doctor_contribution_price'),
        ];
    }
}
