<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAddToTableName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // direktor foydalanuvchi kirtiyabdi
            $table->string('doctor_signature')->nullable(); ///Shifokorning imzosi 
            $table->bigInteger('department_id')->nullable(); ///Shifokorning ixtisosligi
            $table->string('inpatient_share_price')->nullable(); ///Statsionar ulushi
            $table->boolean('is_primary_agent')->nullable(); ///Ushbu agentni asosiy deb belgilaysizmi?
            $table->boolean('owner_id')->nullable(); ///direktor idni
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('doctor_signature');
            $table->dropColumn('department_id');
            $table->dropColumn('inpatient_share');
            $table->dropColumn('is_primary_agent');
            $table->dropColumn('owner_id');
        });
    }
}
