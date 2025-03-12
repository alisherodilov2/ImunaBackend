<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Master extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'full_name',
        'tg_id',
        'phone',
        'is_occupied', // ishda 
        'is_contract',
        'username',
        'status',
        'is_active',
    ];
    public function order()
    {
        return $this->hasMany(Order::class,  'master_id')->whereIn('status',[
            'pay_finally','finally'
        ]);
    }

    public function penaltyAmount()
    {
        return $this->hasMany(PenaltyAmount::class,  'master_id');
    }
    protected $attributes = [
        'is_occupied' => false,
        'is_contract' => false,
        'is_active' => false,
    ];
}
