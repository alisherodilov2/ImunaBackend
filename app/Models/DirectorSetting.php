<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectorSetting extends Model
{
    use HasFactory, Scopes;
    // $table->boolean('is_reg_person_id')->nullable();
    // $table->boolean('is_reg_pay')->nullable();
    // $table->boolean('is_reg_department')->nullable();
    // $table->boolean('is_reg_service')->nullable();
    // $table->boolean('is_reg_queue_number')->nullable();
    // $table->boolean('is_reg_status')->nullable();
    // // sahifalar
    // $table->boolean('is_reg_nav_graph')->nullable();
    // $table->boolean('is_reg_nav_treatment')->nullable();
    // $table->boolean('is_reg_nav_at_home')->nullable();
    // $table->boolean('is_reg_nav_storage')->nullable();
    // $table->boolean('is_reg_nav_expense')->nullable();
    // $table->boolean('is_reg_nav_report')->nullable();
    protected $fillable = [
        'user_id',
        'is_reg_monoblok',
        'is_reg_sex',
        'is_reg_phone',
        'is_reg_data_birth',
        'is_reg_person_id',
        'is_reg_pay',
        'is_reg_department',
        'is_reg_service',
        'is_reg_queue_number',
        'is_reg_status',
        'is_reg_nav_graph',
        'is_reg_nav_treatment',
        'is_reg_nav_at_home',
        'is_reg_nav_storage',
        'is_reg_nav_expense',
        'is_reg_nav_report',
        'is_contribution_doc',
        'is_contribution_kounteragent',
        'is_contribution_kt_doc',
        'is_chek_rectangle',
        'is_reg_address',
        'is_reg_citizenship',
        'is_reg_nav_statsionar',
        'is_reg_mix_pay',
        'is_reg_transfer_pay',
        'is_reg_card_pay',
        'is_reg_pass_number',
        'is_qr_chek',
        'is_logo_chek',
        'result_domain',
        'logo_height',
        'logo_width',
        'domain',
        'is_debt_modal',
        'is_chek_total_price'
    ];
}
