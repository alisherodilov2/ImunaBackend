<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicetypes extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'department_id',
        'type',
    ];
    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
}
