<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdDataToCounterpartySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('counterparty_settings', function (Blueprint $table) {
            $table->longText('treatment_id_data')->nullable();
            $table->longText('ambulatory_id_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('counterparty_settings', function (Blueprint $table) {
            $table->dropColumn('treatment_id_data');
            $table->dropColumn('ambulatory_id_data');
        });
    }
}
