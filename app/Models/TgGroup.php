<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TgGroup extends Model
{
    use HasFactory,Scopes;
    protected $fillable = [
        'title',
        'tg_id',
        'is_send',
    ];
    protected $attributes = [
        'is_send' => false,
    ];
}
