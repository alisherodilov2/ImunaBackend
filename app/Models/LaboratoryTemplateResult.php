<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryTemplateResult extends Model
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
        'is_print',
        'service_id',
        'user_id',
        'client_id',
        'laboratory_template_id',
        'client_value_id',
        'color',
    ];
}
