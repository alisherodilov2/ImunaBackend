<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferringDoctorChangeArchive extends Model
{

    use HasFactory, Scopes;

    protected $fillable = [
        'client_id',
        'to_referring_doctor_id',
        'from_referring_doctor_id',
    ];

    // client
    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    // to_referring_doctor_id
    public function to_referring_doctor()
    {
        return $this->hasOne(ReferringDoctor::class, 'id', 'to_referring_doctor_id');
    }
    // from_referring_doctor_id
    public function from_referring_doctor()
    {
        return $this->hasOne(ReferringDoctor::class, 'id', 'from_referring_doctor_id');
    }


}
