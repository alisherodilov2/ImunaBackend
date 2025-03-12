<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Scopes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    const USER_ROLE_SUPPER_ADMIN = 'super_admin';
    const USER_ROLE_DIRECTOR = 'director';
    const USER_ROLE_RECEPTION = 'reception';
    const USER_ROLE_CASH_REGISTER = 'cash_register';
    const USER_ROLE_DOCTOR = 'doctor';
    const USER_ROLE_LABORATORY = 'laboratory';
    const USER_ROLE_QUEUE = 'queue';
    const USER_ROLE_COUNTERPARTY = 'counterparty';
    const USER_ROLE_PHARMACY = 'pharmacy';
    const USERS_ROLE = [
        self::USER_ROLE_RECEPTION,
        self::USER_ROLE_CASH_REGISTER,
        self::USER_ROLE_DOCTOR,
        self::USER_ROLE_LABORATORY,
        self::USER_ROLE_QUEUE,
        self::USER_ROLE_COUNTERPARTY,
        self::USER_ROLE_PHARMACY
    ];

    protected $fillable = [
        'role',
        'password',
        'off_date',
        'telegram_id',
        'sms_api',
        'license',
        'site_url',
        'user_phone',
        'phone_1',
        'phone_2',
        'phone_3',
        'logo_photo',
        'user_photo',
        'blank_file',
        'address',
        'location',
        'name',
        'login',
        'password',
        'full_name',
        'doctor_signature',
        'department_id',
        'inpatient_share_price', ///statsianra ulush
        'owner_id',
        'is_primary_agent',
        'duration',
        'can_accept',
        'is_payment', /// tolov olish uchun
        'work_start_time',
        'work_end_time',
        'working_days',
        'is_diagnoz',
        'ambulatory_plan_qty',
        'ambulatory_service_id',
        'treatment_plan_qty',
        'treatment_service_id',
        'is_shikoyat',
        'is_cash_reg',
        'is_editor',
        'is_main',
        'is_gijja',
        'is_template',
        'is_marketing',
        'is_certificates',
        'device_id',
        'is_excel_repot'
    ];
    protected $attributes = [
        'is_primary_agent' => false,
        'can_accept' => false,
        'is_shikoyat' => false,
        'is_cash_reg' => false,
        'is_diagnoz' => false,
        'is_payment' => false,
        'is_editor' => false,
        'is_main' => false,
        'duration' => 0,
        'working_days' => '[]',

    ];
    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }

    public function clientResult()
    {
        return $this->hasMany(ClientResult::class, 'doctor_id', 'id');
    }
    public function doctorBalance()
    {
        return $this->hasMany(DoctorBalance::class, 'doctor_id', 'id');
    }
    public function doctorBalanceFind()
    {
        return $this->has(doctorBalance::class, 'doctor_id', 'id');
    }
    // owner
    public function owner()
    {
        return $this->hasOne(User::class, 'id', 'owner_id');
    }
    public function userCounterpartyPlan()
    {
        return $this->hasMany(UserCounterpartyPlan::class);
    }
    public function treatmentService()
    {
        return $this->hasOne(Services::class, 'id', 'treatment_service_id');
    }
    public function ambulatoryService()
    {
        return $this->hasOne(Services::class, 'id', 'ambulatory_service_id');
    }
    public function userTemplateItem()
    {
        return $this->hasMany(UserTemplateItem::class);
    }

    public function referringDoctor()
    {
        return $this->hasMany(ReferringDoctor::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $fileFields = [
        'logo_photo',
        'user_photo',
        'blank_file',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
