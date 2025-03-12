<?php

namespace App\Http\Resources\Repot;

use Illuminate\Http\Resources\Json\JsonResource;

class RepotCcounterpartyResoruce extends JsonResource
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
            'name' => $this->name,
            'full_name' => $this->full_name,
            'owner' => $this->owner,
            'user_phone' => $this->user_phone,
            'referringDoctor' => $this->referringDoctor,
            'docotor_count' => $this->referringDoctor->count(),
            'client_count' => $this->referringDoctor->sum(function($q){
                return $q->referringDoctorBalance->count();
            }),
            'total_price' => $this->referringDoctor->sum(function($q){
                return $q->referringDoctorBalance->sum('total_price');
            }),
            'kounteragent_contribution_price' => $this->referringDoctor->sum(function($q){
                return $q->referringDoctorBalance->sum('total_kounteragent_contribution_price');
            }),
            // 'client_count' => $this->referringDoctor->referringDoctorBalance->count(),
            // 'total_price' => $this->referringDoctor->referringDoctorBalance->sum('total_price'),
            // 'kounteragent_contribution_price' => $this->referringDoctor->referringDoctorBalance->sum('total_kounteragent_contribution_price')
            // ,
        ];
    }
}
