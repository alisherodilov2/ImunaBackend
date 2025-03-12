<?php

namespace App\Http\Resources\DoctorTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return  [
            'id' => $this->id,
            'name' => $this->name,
            'data' => $this->data,
        ];
    }
}
