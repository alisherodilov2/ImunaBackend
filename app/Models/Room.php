<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'client_id',
        'nurse_contribution',
        'doctor_contribution',
        'price',
        'room_index',
        'number',
        'type',
        'is_empty'
    ];
    protected $attributes = [
        'nurse_contribution' => 0,
        'doctor_contribution' => 0,
        'price' => 0,
    ];
}
