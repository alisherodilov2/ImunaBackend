<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayToReferringDoctorBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctor_balances', function (Blueprint $table) {
            $table->string('kounteragent_contribution_price_pay')->nullable(); //Kounteragent ulushi
            $table->string('kounteragent_doctor_contribution_price_pay')->nullable(); //Koun
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referring_doctor_balances', function (Blueprint $table) {
            $table->dropColumn('kounteragent_contribution_price_pay');
            $table->dropColumn('kounteragent_doctor_contribution_price_pay');
        });
    }
}
