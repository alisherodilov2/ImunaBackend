<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientTime extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'agreement_time',
        'department_id',
        'is_active'
    ];
    public function client()
    {
        return $this->hasOne(Client::class,'id', 'client_id');
    }

    protected $attributes = [

            'is_active' => 1,
        ];
    // department
    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
}
