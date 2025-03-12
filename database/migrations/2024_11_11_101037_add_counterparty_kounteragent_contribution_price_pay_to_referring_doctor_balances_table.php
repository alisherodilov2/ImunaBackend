<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCounterpartyKounteragentContributionPricePayToReferringDoctorBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctor_balances', function (Blueprint $table) {
            $table->string('counterparty_kounteragent_contribution_price_pay')->nullable(); //Kounteragent ulushi
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
            $table->dropColumn('counterparty_kounteragent_contribution_price_pay');
        });
    }
}
