<?php

namespace App\Http\Resources\Department;

use App\Models\ClientResult;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $is_time = false;
        $clintResult = ClientResult::
            // ->where('room_id', $this->id)
            where('department_id', $this->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->whereIn('is_check_doctor', ['start', 'pause','finish'])
            ->whereHas('clientValue', function ($q) {
                $q
                    ->where('is_at_home', 0)
                    ->where('is_pay', 1)
                    ->where('queue_number', '>', 0)
                    ->orwherenull('is_at_home')
                    ->where('is_active', 1);
            })
            ->with(['clientValue' => function ($q) {
                $q
                    ->where('is_at_home', 0)
                    ->where('is_pay', 1)
                    ->where('queue_number', '>', 0)
                    ->orwherenull('is_at_home')
                    ->where('is_active', 1);
            }])
            ->latest() // Yoki ->orderBy('id', 'desc')
            ->first();

        $waiting = ClientResult::where('department_id', $this->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->whereNull('is_check_doctor')
            ->whereHas('clientValue', function ($q) {
                $q
                    ->where('is_at_home', 0)
                    ->where('is_pay', 1)
                    ->orwherenull('is_at_home')
                    ->where('is_active', 1);
            })
            // function minutesToSeconds($minutes)
            // {
            //     return $minutes * 60;
            // }
            // Yoki ->orderBy('id', 'desc')
            ->count();
        // Boshlanish vaqtini Carbon formatiga o'girish
        $workedSeconds = 0;
        if (isset($clintResult) && isset($clintResult->start_time)) {
            if (($clintResult->is_check_doctor) == 'pause' ) {
                $start = Carbon::createFromFormat('H:i:s', $clintResult->start_time);
                if (isset($start)) {
                    $now = Carbon::now();

                    // Farqni sekundlarda olish
                    $workedSeconds = $this->duration * 60 -$clintResult->use_duration;
                }
            } else if (($clintResult->is_check_doctor) == 'start') {
                $is_time = true;
                $start = Carbon::createFromFormat('H:i:s', $clintResult->start_time);
                if (isset($start)) {
                    $now = Carbon::now();
                    // Farqni sekundlarda olish
                    $duration = $this->duration * 60 -(    $now->diffInSeconds($start) -  $this->use_duration);
                    if($this->duration - $this->use_duration > 0){
                        $workedSeconds = $duration;
                        
                    }
                    $workedSeconds = $now->diffInSeconds($start);
                }
            }


            // Hozirgi vaqtni olish


            // 'start_time' => now()->format('H:i:s'),
            // 'use_duration' => $clientResult->use_duration + $this->getWorkedTimeInSeconds($clientResult->start_time),
            // 'duration' => +$clientResult->duration > 0 ? $clientResult->duration : $this->minutesToSeconds($departament->duration),
        }

        return  [
            'id' => $this->id,
            'queue' => isset($clintResult) ? $clintResult->clientValue->last()->queue_number : 0,
            'time' => $workedSeconds,
            'is_time' => $is_time,
            'clintResult' => $clintResult,
            'waiting' => $waiting,
            'name' => $this->name,
            'floor' => $this->floor,
            'main_room' => $this->main_room,
            'room_type' => $this->room_type,
            'room_number' => $this->room_number,
            'letter' => $this->letter,
            'department' => $this->department,
            // 'department_value' => []
            // 'department_value' => MonitorResource::collection($this->departmentValue),
        ];
    }
}
