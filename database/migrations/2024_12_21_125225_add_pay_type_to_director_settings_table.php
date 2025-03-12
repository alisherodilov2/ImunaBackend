<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayTypeToDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->boolean('is_reg_card_pay')->nullable();
            $table->boolean('is_reg_transfer_pay')->nullable();
            $table->boolean('is_reg_mix_pay')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->dropColumn('is_reg_card_pay');
            $table->dropColumn('is_reg_transfer_pay');
            $table->dropColumn('is_reg_mix_pay');
        });
    }
}
