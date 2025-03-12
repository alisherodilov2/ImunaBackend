<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TgBotConnect extends Model
{
    use HasFactory,Scopes;
    protected $fillable = [
        'key',
        'user_id',
        'tg_id',
    ];
}
