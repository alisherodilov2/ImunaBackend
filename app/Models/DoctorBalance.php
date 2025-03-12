<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorBalance extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'doctor_id',
        'service_count',
        'client_id',
        'department_id',
        'total_price',
        'contribution_data',
        'doctor_contribution_price_pay',
        'total_doctor_contribution_price',
        'is_statsionar',
        'date',
        'daily_repot_id'
    ];
    // client
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    protected $attributes = [
        'contribution_data' => [],
    ];
    // daily_repot_id
    public function dailyRepot()
    {
        return $this->hasOne(DailyRepot::class, 'id', 'daily_repot_id');
    }
}
