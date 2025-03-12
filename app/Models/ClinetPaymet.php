<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinetPaymet extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'full_name',
        'pay_type',
        'cash_price',
        'card_price',
        'transfer_price',
        'back_total_price',
        'pay_total_price',
        'discount',
        'discount_comment',
        'payment_deadline',
        'debt_price',
        'debt_comment',
        'total_price',
        'user_id',
        'client_value_id_data',
        'is_room',
        'balance'
    ];
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    protected $attributes = [
        'client_value_id_data' => [],
    ];
    public function clientTimeArchive()
    {
        return $this->hasMany(ClientTimeArchive::class);
    }
}
