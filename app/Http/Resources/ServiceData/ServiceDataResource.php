<?php

namespace App\Http\Resources\ServiceData;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceDataResource extends JsonResource
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
            'department' => $this->department,
            'servicetype' => $this->servicetype,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'price' => $this->price,
            'laboratory_template_count' => $this->laboratory_template_count ?? 0,
            'doctor_contribution_price' => $this->doctor_contribution_price,
            'kounteragent_contribution_price' => $this->kounteragent_contribution_price,
            'kounteragent_doctor_contribution_price' => $this->kounteragent_doctor_contribution_price,
            'is_change_price' => $this->is_change_price,
    
            'service_product' => $this->serviceProduct,
        ];
    }
}
