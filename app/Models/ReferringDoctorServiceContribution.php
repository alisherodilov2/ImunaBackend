<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferringDoctorServiceContribution extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'service_id',
        'ref_doc_id',
        'contribution_price',
    ];
}
