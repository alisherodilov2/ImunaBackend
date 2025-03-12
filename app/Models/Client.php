<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'first_name',
        'last_name',
        'parent_name',
        'data_birth',
        'phone',
        'citizenship',
        'sex',
        'price',
        'person_id',
        'probirka_count',
        'doctor_id', ///tekshirigan shifokor
        'parent_id',
        'user_id',
        'department_id',
        'total_price',
        'service_count',
        'debt_price',
        'discount',
        // 'discount_price',
        'is_pay',
        'payment_deadline',
        'pay_total_price',
        // 'queue_number',
        'duration',
        'use_duration',
        'start_time',
        'is_check_doctor',
        'room_id',
        'back_total_price',
        'address',
        'use_status',
        'department_count',
        'finish_department_count',
        'referring_doctor_id',
        'advertisements_id',
        'balance',
        'queue_letter',
        // stationar
        'reg_diagnosis',
        'admission_date',
        'statsionar_doctor_id',
        'is_statsionar',
        'statsionar_room_id',
        'finish_statsionar_date',
        'is_finish_statsionar',
        'statsionar_parent_id',
        'statsionar_room_price',
        'statsionar_room_discount',
        'statsionar_room_qty',
        'statsionar_room_price_pay',
        'pass_number',
        'probirka_id',
        'is_sms',
        'day_qty',
    ];

    const STATUS_FINISH = 'finish';
    const STATUS_PAUSE = 'pause';
    const STATUS_START = 'start';
    const STATUS_IN_ROOM = 'in_room';
    const STATUS_IN_WAIT = 'waiting';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_MIX = 'mix';




    public function statsionarDoctor()
    {
        return $this->hasOne(User::class, 'id', 'statsionar_doctor_id');
    }

    public function statsionarRoom()
    {
        return $this->hasOne(Room::class, 'id', 'statsionar_room_id');
    }

    public function clientItem()
    {
        return $this->hasMany(Client::class,  'parent_id');
    }
    public function clientItemFirst()
    {
        return $this->hasMany(Client::class, 'parent_id')->whereNull('parent_id')->first();
    }


    public function grapaechveChek()
    {
        return $this->hasMany(GraphArchive::class,  'person_id', 'person_id')->where([
            'status' => 'live',
            'use_status' => 'treatment'
        ]);
    }


    public function balance()
    {
        return $this->hasOne(Client::class,  'id', 'parent_id')->whereNull('parent_id');
    }
    public function currentBalance()
    {
        return $this->hasOne(Client::class,  'id', 'parent_id')->whereNull('parent_id');
    }
    public function clientTime()
    {
        return $this->hasMany(ClientTime::class);
    }
    public function referringDoctor()
    {
        return $this->hasOne(ReferringDoctor::class, 'id', 'referring_doctor_id');
    }
    public function clientResult()
    {
        return $this->hasMany(ClientResult::class,  'client_id');
    }
    public function clientResultCheck()
    {
        return $this->hasMany(ClientResult::class,  'client_id')->where(['doctor_id' => auth()->id(), 'department_id' => auth()->user()->department_id]);
    }
    public function clientValue()
    {
        return $this->hasMany(ClientValue::class, 'client_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function parent()
    {
        return $this->hasOne(Client::class, 'id', 'parent_id');
    }
    public function parentall()
    {
        return $this->belongsTo(Client::class, 'parent_id');
    }
    public function parentChek()
    {
        return $this->hasOne(Client::class, 'id', 'parent_id')->whereNull('parent_id');
    }
    public function clientPayment()
    {
        return $this->hasMany(ClinetPaymet::class, 'client_id');
    }
    public function doctor()
    {
        return $this->hasOne(User::class, 'id', 'doctor_id');
    }
    public function templateResult()
    {
        return $this->hasMany(ResultTemplate::class, 'client_id');
    }
    public function person()
    {
        return $this->hasOne(Client::class, 'person_id', 'person_id');
    }
    public function graphAchive()
    {
        return $this->hasMany(GraphArchive::class, 'person_id', 'person_id');
    }


    public function laboratoryTemplateResultFiles()
    {
        return $this->hasMany(LaboratoryTemplateResultFiles::class);
    }
    public function clientCertificate()
    {
        return $this->hasOne(ClientCertificate::class);
    }
    public function clientCertificateAll()
    {
        return $this->hasMany(ClientCertificate::class);
    }
    protected $attributes = [
        'service_count' => 0,
        'finish_department_count' => 0,
        'department_count' => 0,
        'total_price' => 0,
        'probirka_count' => 0,
        'debt_price' => 0,
        // 'discount_price' => 0,
        'pay_total_price' => 0,
        'use_duration' => 0,
        'duration' => 0,
        'is_pay' => 0,
        'discount' => 0,
        'back_total_price' => 0,
        'is_finish_statsionar' => 0
    ];
}
