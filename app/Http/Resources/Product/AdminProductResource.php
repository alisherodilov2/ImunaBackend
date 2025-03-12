<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $parent = parent::toArray($request);
        return [
            // 'current_page' => $parent['current_page'],
            // 'last_page' => $parent['last_page'],
            // 'total' => $parent['total'],
            // 'data' => ProductResource::collection($parent['data']),
            // ...$parent
            'id' => $this?->id,
        ];
    }
}
