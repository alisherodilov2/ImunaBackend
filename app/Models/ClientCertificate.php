<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCertificate extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'doctor_id',
        'client_id',
        'date_2',
        'date_1',
        'serial_number_1',
        'serial_number_2',
        'department_id'
    ];
    // departments
    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
}
