<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'id' => isset($this?->id) ? $this?->id : null,
            'email' => isset($this?->email) ? $this?->email : null,
            'name' => isset($this?->name) ? $this?->name : null,
            'role' => isset($this?->role) ? $this?->role : null,
            'history' => $this['history'],
            'date' => now()->format('Y-m-d'),
        ];
    }
}
