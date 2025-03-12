<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContributionToReferringDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctors', function (Blueprint $table) {
            $table->string('doctor_contribution_price')->nullable(); //Doktor ulushi
            $table->string('kounteragent_contribution_price')->nullable(); //Kounteragent ulushi
            $table->string('kounteragent_doctor_contribution_price')->nullable(); //Kounterdoktor ulushi //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referring_doctors', function (Blueprint $table) {
            $table->dropColumn('doctor_contribution_price');
            $table->dropColumn('kounteragent_contribution_price');
            $table->dropColumn('kounteragent_doctor_contribution_price');
        });
    }
}
