<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBalance extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'person_id',
        'status',
        'price',
        'pay_type',
        'daily_repot_id',
    ];
    // client
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}
