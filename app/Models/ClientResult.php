<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientResult extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'duration',
        'room_id',
        'doctor_id',
        'start_time',
        'is_check_doctor',
        'department_id',
        'use_duration'

    ];

    public function clientValue()
    {
        return $this->hasMany(ClientValue::class, 'client_id', 'client_id');
    }


    // doctor
    public function doctor()
    {
        return $this->hasOne(User::class, 'id', 'doctor_id');
    }
    public function doctorBalnce()
    {
        return $this->hasMany(ReferringDoctorBalance::class, 'client_id', 'client_id');
    }
    public function department()
    {
        return $this->hasMany(Departments::class, 'id', 'department_id');
    }
    public function departmentFirst()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
}
