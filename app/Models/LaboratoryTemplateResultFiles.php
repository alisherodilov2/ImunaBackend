<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryTemplateResultFiles extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'client_id',
        'file',
        'type',
        'name',
        'servicetype_id',
    ];
    public $qty = 75;
    public $fileFields = [
        'file',
    ];
}
