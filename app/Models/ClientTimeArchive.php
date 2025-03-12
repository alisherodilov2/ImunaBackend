<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientTimeArchive extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'agreement_time',
        'department_id',
        'clinet_paymet_id'
    ];
}
