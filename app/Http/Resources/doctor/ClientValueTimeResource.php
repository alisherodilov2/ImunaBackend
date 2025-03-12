<?php

namespace App\Http\Resources\doctor;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientValueTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $time = 0;
        $createdAt = Carbon::parse($this->created_at); // `created_at` sanasini bu yerda belgilaysiz
        $today = Carbon::today();
        $clientResult = $this->clientResult->first();
        if ($clientResult->is_check_doctor == 'start' && !$createdAt->lessThan($today)) {
            // if( $this->use_duration < oldGetWorkedTimeInSeconds($this->start_time)-$this->use_duration){
            //     $time = 0;
            // }else{
            // }
            $time = ($clientResult->duration - $clientResult->use_duration) - oldGetWorkedTimeInSeconds($clientResult->start_time);
            if ($time <= 0) {
                $time = 0;
            }
        } else  if ($clientResult->is_check_doctor == 'pause' && !$createdAt->lessThan($today)) {
            $time  = $clientResult->duration - $clientResult->use_duration;
        }

        return [

            ...parent::toArray($request),
            'time' =>  $time,

        ];
    }
}
