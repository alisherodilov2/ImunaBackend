<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'department_id',
        'servicetype_id',
        'name',
        'price',
        'doctor_contribution_price',
        'kounteragent_contribution_price',
        'kounteragent_doctor_contribution_price',
        'is_change_price',
        'short_name',
    ];
    public function department()
    {
        return $this->hasOne(Departments::class, 'id',  'department_id');
    }
    public function laboratoryTemplate()
    {
        return $this->hasMany(LaboratoryTemplate::class, 'service_id', 'id');
    }

    public function serviceProduct()
    {
        return $this->hasMany(ServiceProduct::class, 'service_id', 'id');
    }
    public function servicetype()
    {
        return $this->hasOne(Servicetypes::class, 'id', 'servicetype_id');
    }
    protected $attributes = [
        'doctor_contribution_price' => 0,
        'kounteragent_contribution_price' => 0,
        'kounteragent_doctor_contribution_price' => 0,
        'is_change_price' => false,
    ];

    // // ReferringDoctorServiceContribution
    // public function referringDoctorServiceContribution()
    // {
    //     return $this->hasOne(ReferringDoctorServiceContribution::class, 'service_id', 'id');
    // }
}
