<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientValue extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'service_id',
        'price',
        'is_active',
        'is_probirka',
        'pay_price',
        'department_id',
        'qty',
        'total_price',
        'queue_number',
        'discount',
        'is_pay',
        'user_id',
        'is_at_home',
        'result',
    ];
    public function service()
    {
        return $this->hasOne(Services::class, 'id', 'service_id');
    }

    public function laboratoryTemplateResult()
    {
        return $this->hasMany(LaboratoryTemplateResult::class);
    }

    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
   

    public function owner()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function clientUseProduct()
    {
        return $this->hasMany(ClientUseProduct::class);
    }
    protected $attributes = [
        'is_active' => true,
        'is_probirka' => false,
        'is_pay' => false,
        'qty' => 1,
        'pay_price' => 0,
        'queue_number' => 0,
        'discount' => 0,
        'is_at_home' => 0
        // 'discount' => 0,
    ];
}
