<?php

namespace App\Http\Resources\Client;

use App\Models\Departments;
use App\Models\DirectorSetting;
use App\Models\Services;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionClientItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'discount' => $this->discount,
            'pay_total_price' => $this->pay_total_price,
            'total_price' => $this->total_price,
        ];
        // 'is_reg_department',
        // 'is_reg_service',
        // 'is_reg_queue_number',
        $directorSetting = DirectorSetting::where('user_id', auth()->user()->owner_id)->first();
        if ($directorSetting->is_reg_department) {
            $data['department'] = Departments::whereIn('id', $this->clientValue->pluck('department_id'))->get('name');
        }
        if ($directorSetting->is_reg_service) {
            $data['service'] = Services::whereIn('id', $this->clientValue->pluck('service_id')) ->get('name');
        }
        return $data;
    }
}
