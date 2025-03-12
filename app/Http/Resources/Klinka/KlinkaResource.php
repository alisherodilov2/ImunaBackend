<?php

namespace App\Http\Resources\Klinka;

use Illuminate\Http\Resources\Json\JsonResource;

class KlinkaResource extends JsonResource
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
            'logo_photo' => $this->logo_photo,
            'blank_file' => $this->blank_file,
            'name' => $this->name,
            'address' => $this->address,
            'full_name' => $this->full_name,
            'telegram_id' => $this->full_name,
            'sms_api' => $this->sms_api,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'phone_3' => $this->phone_3,
            'user_phone' => $this->user_phone,
            'off_date' => $this->off_date,
            'location' => $this->location,
            'user_photo' => $this->user_photo,
            'site_url' => $this->site_url,
            'license' => $this->license,
            'is_gijja' => $this->is_gijja,
            'is_marketing' => $this->is_marketing,
            'is_template' => $this->is_template,
            'device_id' => $this->device_id,
            'is_excel_repot' => $this->is_excel_repot,
        ];
    }
}
