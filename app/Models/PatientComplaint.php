<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientComplaint extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'name',
        'user_id',
    ];
}
