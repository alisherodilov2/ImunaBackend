<?php

namespace App\Http\Resources\Client;

use App\Models\Client;
use App\Models\Services;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $user = auth()->user();
        $welcome_count =0;
        if( $user->role == User::USER_ROLE_RECEPTION){
            $welcome_count = Client::where(['user_id'=> $user->id,'parent_id'=>$this->id])->count();
        } 
        if($user->role == User::USER_ROLE_DOCTOR ){

            $serviceId = Services::where('department_id', $user->department_id)->pluck('id');
            $welcome_count = Client::where(['parent_id'=>$this->id])
            ->whereHas('clientValue', function ($q) use ($serviceId) {
                $q
                ->whereRaw('pay_price - price = 0')
                ->whereIn('service_id',   $serviceId);
            })->with('clientValue')
            ->count();
        }
        return  [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'person_id' => $this->person_id,
            'data_birth' => $this->data_birth,
            'citizenship' => $this->citizenship,
            'sex' => $this->sex,
            'client_item' => $this->clientItem,
            'welcome_count' => $welcome_count,
            'use_status' => $this->use_status,
            'pass_number' => $this->pass_number,
            'balance' => $this->balance,

        ];
    }
}
