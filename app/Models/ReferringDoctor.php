<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferringDoctor extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'first_name',
        'last_name',
        'workplace',
        'phone',
        'user_id',
        'total_price',
        'client_count',
        'doctor_contribution_price',
        'kounteragent_contribution_price',
        'kounteragent_doctor_contribution_price',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function referringDoctorBalance()
    {
        return $this->hasMany(ReferringDoctorBalance::class);
    }
    public function referringDoctorPay()
    {
        return $this->hasMany(ReferringDoctorPay::class);
    }
    public function graphArchive()
    {
        return $this->hasMany(GraphArchive::class)->where('status', 'live');
    }
    public function client()
    {
        return $this->hasMany(Client::class, 'referring_doctor_id', 'id')->whereNull('parent_id');
    }

    public function referringDoctorServiceContribution()
    {
        return $this->hasOne(ReferringDoctorServiceContribution::class);
    }
}
