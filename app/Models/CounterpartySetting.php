<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterpartySetting extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'treatment_plan_qty',
        'ambulatory_plan_qty',
        'treatment_service_price',
        'treatment_service_kounteragent_price',
        'ambulatory_service_price',
        'ambulatory_service_kounteragent_price',
        'treatment_service_id',
        'ambulatory_service_id',
        'counterparty_id',
        'user_id',
        'ambulatory_id_data',
        'treatment_id_data',
      
    ];
}
