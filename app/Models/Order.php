<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'full_name',
        'address',
        'price',
        'status',
        'qty',
        'master_id',
        'master_salary',
        'is_check',
        'is_finish',
        'is_freeze',
        'comment',
        'master_salary_pay',
        'phone',
        'operator_id',
        'finish_date',
        'customer_id',
        'installation_time',
        'target_adress',
        'warranty_period_type', //// day,week,month,year
        'warranty_period_quantity', //
        'warranty_period_date', //
        'is_installation_time', //


    ];
    public function master()
    {
        return $this->hasOne(Master::class, 'id', 'master_id');
    }
    public function penaltyAmount()
    {
        return $this->hasMany(PenaltyAmount::class,  'order_id');
    }
    public function operator()
    {
        return $this->hasOne(User::class, 'id', 'operator_id');
    }
    protected $attributes = [
        'is_check' => false,
        'is_finish' => false,
        'is_freeze' => false,
        'is_installation_time' => false,
    ];
}
