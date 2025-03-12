<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SendGroupOrder extends Model
{
    use HasFactory,Scopes;
    protected $fillable = [
        'msg_id',
        'order_id',
        'group_id',
        'chat_id',
    ];
}
