<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryClientItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $result = $this->clientValue->filter(function ($item) {
            return ($item->service->laboratoryTemplate->count() > 0 ? $item->laboratoryTemplateResult->where('is_print', 1)->count() ==  $item->service->laboratoryTemplate->count() - 1 : 0);
        })->count();
        $service = $this->clientValue->count();

        return [
            'id' => $this->id,
            'client_value' => [
                'service' => $service,
                'result' => $result
            ],
            'is_result' =>$this->clientResult && $this->clientResult?->first()?->is_check_doctor == 'finish' ? true : false,
            'probirka_id' => $this->probirka_id,
            'is_sms' => $this->is_sms,
            'created_at' => $this->created_at,
        ];
    }
}
