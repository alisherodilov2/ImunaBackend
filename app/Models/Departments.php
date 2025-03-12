<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    use HasFactory, Scopes;
    const STATUS_DOCTOR = 'doctor';
    const STATUS_CERTIFICATE = 'certificate';
    const STATUS_PROBIRKA = 'probirka';
    protected $fillable = [
        'user_id',
        'name',
        'floor',
        'main_room',
        'letter',
        'probirka',
        'parent_id',
        'room_number',
        'room_type',
        'working_days',
        'work_end_time',
        'work_start_time',
        'duration',
        'empty',
        'is_payment',
        'client_id',
        'is_chek_print',
        'is_reg_time',
        'is_graph_time',
        'is_queue_number',
        'queue_number_limit',
        'shelf_number_limit',
        'is_certificate',
        'is_operation'

    ];
    public function departmentTemplateItem()
    {
        return $this->hasMany(DepartmentTemplateItem::class, 'department_id', 'id');
    }
    public function departmentValue()
    {
        return $this->hasMany(Departments::class,  'parent_id');
    }

    public function floorRoom()
    {
        return $this->hasMany(Departments::class,  'floor', 'floor')->whereNull('parent_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'id',  'department_id');
    }
    public function client()
    {
        return $this->hasOne(Client::class, 'id',  'client_id');
    }
    protected $attributes = [
        'probirka' => false,
        'is_payment' => false,
        'empty' => false,
        'shelf_number_limit' => 0
    ];
}
