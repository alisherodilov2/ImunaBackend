<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryTemplate extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'name',
        'result',
        'user_id',
        'service_id',
        'normal',
        'extra_column_1',
        'extra_column_2',
        'is_result_name',
    ];
}
