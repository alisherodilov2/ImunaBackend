<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferringDoctorIdToReferringDoctorPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctor_pays', function (Blueprint $table) {
            $table->bigInteger('referring_doctor_id')->nullable(); //Kounterage
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referring_doctor_pays', function (Blueprint $table) {
            $table->dropColumn('referring_doctor_id');
        });
    }
}
