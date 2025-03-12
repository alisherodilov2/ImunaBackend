<?php

namespace App\Http\Resources\ReferringDoctor;

use Illuminate\Http\Resources\Json\JsonResource;

class ReferringDoctorResource extends JsonResource
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
            'first_name' => $this->first_name,
            'referring_doctor_balance' => $this->referringDoctorBalance,
            'last_name' => $this->last_name,
            'workplace' => $this->workplace,
            'phone' => $this->phone,
            'graph_archive' => $this->graphArchive,
            'client_count' => $this->referringDoctorBalance->count(),
            'total_price' => $this->referringDoctorBalance->sum('total_price'),
            'doctor_contribution_price' => $this->referringDoctorBalance->sum('total_doctor_contribution_price'),
            'kounteragent_contribution_price' =>  $this->referringDoctorBalance->sum('total_kounteragent_contribution_price'),
            'kounteragent_doctor_contribution_price' => $this->referringDoctorBalance->sum('total_kounteragent_doctor_contribution_price'),
        ];
    }
}
