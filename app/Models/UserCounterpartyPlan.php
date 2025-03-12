<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCounterpartyPlan extends Model
{
    use HasFactory,Scopes;
    protected $fillable = [
        'user_id',
        'service_id',
        'status',
    ];
    public function service()
    {
        return $this->hasOne(Services::class, 'id', 'service_id');
    }

}
