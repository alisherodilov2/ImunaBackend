<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'full_name',
        'address',
        'phone',
        'target_adress',
    ];

    public function order()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id')->whereIn('status',[
            'pay_finally','finally'
        ]);
    }
}
