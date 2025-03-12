<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
{

    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'name',
        
    ];
}
