<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    //  1. supper admin

    // url/admin bolishi kerak
    // login, parol

    // clinika crud bolishi kerak
    // 2.rollar 
    // klinka yaratish ikkta step
    // 1spet
    // klinka nomi , logosi,manzili ,googl karta sliklasini qoyadi,smsapi(qoyish kerak input), telefon nomer(3ta input),litzensiya(input),sayt, telegram_id ,ochish sanasi,klinka blankasi(rasm boladi),
    // 2 step
    // klinka director malutolarni toldirish
    // i.f, telefon, rasm, parol


    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role')->nullable();///Xodimning mustaxasisligi
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->string('logo_photo')->nullable(); //klinka logosi  rasmi
            $table->string('user_photo')->nullable(); //klinka direktor rasmi
            $table->string('name')->nullable(); //klinka nomi
            $table->string('full_name')->nullable(); //klinka direktor ism familyasi
            $table->string('address')->nullable(); //klinka manzil
            $table->string('location')->nullable(); //klinka  lokatsiya
            $table->string('sms_api')->nullable(); //sms uchun api
            $table->string('phone_1')->nullable(); //klinka telfon raqam 1
            $table->string('phone_2')->nullable(); //klinka telfon raqam 1
            $table->string('phone_3')->nullable(); //klinka telfon raqam 1
            $table->string('user_phone')->nullable(); //klinka telfon raqam 1
            $table->string('license')->nullable(); //klinka litzensiyasi
            $table->string('telegram_id')->nullable(); //klinka telegram id
            $table->string('off_date')->nullable(); //ochish sanasi
            $table->string('blank_file')->nullable(); //klinka blankasi/ shifokor blankasi
            $table->string('site_url')->nullable(); //sayt

          
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
