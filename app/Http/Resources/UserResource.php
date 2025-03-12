<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
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
        $auth = Auth::user() ?? 0;
        if($auth){
            return [
                'id'=>$this->id,
                'name'=>$this->name,
                'email'=>$this->email,
                'updated_at'=>$this->updated_at,
                'created_at'=>$this->updated_at,
            ];
        }else{
            return [
                'name'=>$this->name,
                'email'=>$this->email,
            ];
        }
    }
}
