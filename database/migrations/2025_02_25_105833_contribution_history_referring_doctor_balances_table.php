<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ContributionHistoryReferringDoctorBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctor_balances', function (Blueprint $table) {
            $table->longText('contribution_history')->nullable();
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
            $table->dropColumn('contribution_history');
        });
    }
}
