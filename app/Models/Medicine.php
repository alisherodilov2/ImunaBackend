<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'medicine_type_id',
        'type',
        'day',
        'many_day',
        'qty',
        'comment',
        'name'

    ];
}
