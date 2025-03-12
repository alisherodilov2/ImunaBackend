<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferringDoctorBalance extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'referring_doctor_id',
        'client_id',
        'total_price',
        'total_doctor_contribution_price',
        'total_kounteragent_contribution_price',
        'total_kounteragent_doctor_contribution_price',
        'date',
        'service_count',
        'kounteragent_contribution_price_pay',
        'kounteragent_doctor_contribution_price_pay',
        'counterparty_kounteragent_contribution_price_pay',
        'is_statsionar',
        'contribution_history'
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    public function clientValue()
    {
        return $this->hasMany(ClientValue::class, 'client_id', 'client_id');
    }
    protected $attributes = [
        'kounteragent_contribution_price_pay' => 0,
        'kounteragent_doctor_contribution_price_pay' => 0,
        'counterparty_kounteragent_contribution_price_pay' => 0,
        'is_statsionar' => 0,
        'contribution_history'=>"[]"
    ];
}
