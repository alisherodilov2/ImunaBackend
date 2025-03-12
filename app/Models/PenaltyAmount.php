<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyAmount extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'price',
        'order_id',
        'master_id',
    ];
    public function master()
    {
        return $this->hasOne(Master::class, 'id', 'master_id');
    }
}
