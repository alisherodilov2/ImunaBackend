<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatsionarToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_statsionar')->nullable();
            $table->bigInteger('statsionar_doctor_id')->nullable();
            $table->bigInteger('statsionar_room_id')->nullable();
            $table->string('admission_date')->nullable();
            $table->string('finish_statsionar_date')->nullable();
            $table->longText('reg_diagnosis')->nullable();
            $table->boolean('is_finish_statsionar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_statsionar');
            $table->dropColumn('statsionar_doctor_id');
            $table->dropColumn('statsionar_room_id');
            $table->dropColumn('admission_date');
            $table->dropColumn('reg_diagnosis');
            $table->dropColumn('finish_statsionar_date');
            $table->dropColumn('is_finish_statsionar');
        });
    }
}
