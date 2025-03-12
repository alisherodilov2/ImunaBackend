<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferringDoctorPay extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'referring_doctor_id',
        'date',
        'counterparty_id',
        'referring_doctor_balance_id',
        'user_id',
        'doctor_contribution_price',
        'kounteragent_contribution_price',
        'kounteragent_doctor_contribution_price',
    ];
}
