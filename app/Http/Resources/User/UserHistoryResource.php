<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserHistoryResource extends JsonResource
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
            'id' => $this?->id,
            'email' => $this?->email,
            'role' => $this?->role,
            'code' => $this?->code,
            'tg_id' => $this?->tg_id,
            'customers' => $this?->customers,
            'branchs' => $this?->branchs,
            'user_information' => new UserInformationResource($this?->userInformation),
            'user_service_situations' => $this?->userServiceSituations,
            'payments' => $this?->payments,
        ];
    }
}
