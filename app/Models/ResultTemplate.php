<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultTemplate extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'status',
        'value',
        'client_id',
        'template_id',
        'template_item_id',
        'client_result_id',
        'description',
        'doctor_template_id'
       
    ];
    public function doctorTemplate()
    {
        return $this->hasOne(DoctorTemplate::class, 'id', 'doctor_template_id');
    }
    public function clientResult()
    {
        return $this->hasOne(clientResult::class, 'id', 'client_result_id');
    }
    // client_result_id
  
}
