<?php

namespace App\Http\Resources\Repot;

use App\Models\GraphArchive;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class RepotReferringDoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // $q->whereNotIn(
        //     'person_id',
        //     GraphArchive::whereIn(
        //         'user_id',
        //         User::where('owner_id', auth()->id())->pluck('id')
        //     )->pluck('person_id')->unique()->toArray()
        // );
        $client = $this->client;
        Log::info('cliebnt',[$client]);
        $muolajaid = GraphArchive::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))->pluck('person_id')->unique()->toArray();
        return
            [
                'id' => $this->id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'client' => $this->client,

                'ambulatory' =>  $client->whereNotIn('person_id',$muolajaid)->count(),
                'treatment' => $client->sum(function($q){
                return    $q->graphAchive
                ->where('status','live')
                ->where('referring_doctor_id',$this->id)
                // referring_doctor_id
                ->count();
                }),
                'finish' => $client->sum(function($q){
                    return   $q->graphAchive->where('status','finish')
                    ->where('referring_doctor_id',$this->id)
                    ->count();
                }),
                'archive' =>$client->sum(function($q){
                    return   $q->graphAchive->where('status','archive')
                    ->where('referring_doctor_id',$this->id)
                    ->count();
                }),
            ];
    }
}
