<?php

namespace App\Http\Resources\Department;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'name' => $this->name,
            'floor' => $this->floor,
            'main_room' => $this->main_room,
            'letter' => $this->letter,
            'probirka' => $this->probirka,
            'department' => $this->department,
            'department_value' => $this->departmentValue,
            'user' => $this?->user ?? [],
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
            'working_days' => $this->working_days,
            'department_template_item' => $this->departmentTemplateItem,
            'duration' => $this->duration,
            'is_chek_print' => $this->is_chek_print,
            'is_graph_time' => $this->is_graph_time,
            'is_reg_time' => $this->is_reg_time,
            'is_queue_number' => $this->is_queue_number,
            'queue_number_limit' => $this->queue_number_limit,
            'shelf_number_limit' => $this->shelf_number_limit,
            'is_certificate' => $this->is_certificate,
            'is_operation' => $this->is_operation,
        ];
    }
}
