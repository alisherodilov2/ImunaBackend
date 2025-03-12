<?php

namespace App\Http\Resources\User;

use App\Http\Resources\User\UserInformationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'role' => $this->role,
            'department' => $this->department,
            'user_photo' => $this->user_photo,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'user_phone' => $this->user_phone,
            'doctor_signature' => $this->doctor_signature,
            'is_primary_agent' => $this->is_primary_agent,
            'blank_file' => $this->blank_file,
            'can_accept' => $this->can_accept,
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
            'working_days' => $this->working_days,
            'duration' => $this->duration,
            'user_template_item' => $this->userTemplateItem,
            'is_shikoyat' => $this->is_shikoyat,
            'is_diagnoz' => $this->is_diagnoz,
            'is_editor' => $this->is_editor,
            'is_main' => $this->is_main,
            'is_cash_reg' => $this->is_cash_reg,
            'is_certificates' => $this->is_certificates,
            'is_payment' => $this->is_payment,
            'ambulatory_service' => $this->ambulatoryService,
            'treatment_service' => $this->treatmentService,
            'treatment_plan_qty' => $this->treatment_plan_qty,
            'ambulatory_plan_qty' => $this->ambulatory_plan_qty,
            'user_counterparty_plan' => $this->userCounterpartyPlan ?? null,
        ];
    }
}
