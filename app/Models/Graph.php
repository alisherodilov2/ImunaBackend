<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Scopes;

class Graph extends Model
{

    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'person_id',
        'data_birth',
        'sex',
        'citizenship',
        'client_id',
        'treatment_id',
        'department_id',
        'at_home_client_id'
    ];
    public function atHomeClient()
    {
        return $this->hasOne(Client::class, 'id', 'at_home_client_id');
    }
    protected $attributes = [
        'treatment_id' => 0,
    ];
    public function person()
    {
        return $this->belongsTo(Client::class, 'person_id', 'person_id')->whereNull('parent_id');
    }
    public function department()
    {
        return $this->hasOne(Departments::class, 'id',  'department_id');
    }
    public function graphArchive()
    {
        return $this->hasOne(GraphArchive::class);
    }
    public function treatment()
    {
        return $this->hasOne(Treatment::class, 'id', 'treatment_id');
    }
    public function graphItem()
    {
        return $this->hasMany(GraphItem::class, 'graph_id');
    }
    public function client()
    {
        return $this->hasMany(Client::class, 'id', 'client_id');
    }
}
