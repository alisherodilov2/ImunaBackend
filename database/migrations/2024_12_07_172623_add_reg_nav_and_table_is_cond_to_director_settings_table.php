<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegNavAndTableIsCondToDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->boolean('is_reg_person_id')->nullable();
            $table->boolean('is_reg_pay')->nullable();
            $table->boolean('is_reg_department')->nullable();
            $table->boolean('is_reg_service')->nullable();
            $table->boolean('is_reg_queue_number')->nullable();
            $table->boolean('is_reg_status')->nullable();
            // sahifalar
            $table->boolean('is_reg_nav_graph')->nullable();
            $table->boolean('is_reg_nav_treatment')->nullable();
            $table->boolean('is_reg_nav_at_home')->nullable();
            $table->boolean('is_reg_nav_storage')->nullable();
            $table->boolean('is_reg_nav_expense')->nullable();
            $table->boolean('is_reg_nav_report')->nullable();
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
            $table->dropColumn('is_reg_person_id');
            $table->dropColumn('is_reg_pay');
            $table->dropColumn('is_reg_department');
            $table->dropColumn('is_reg_service');
            $table->dropColumn('is_reg_queue_number');
            $table->dropColumn('is_reg_status');
            //  sahifalar
            $table->dropColumn('is_reg_nav_graph');
            $table->dropColumn('is_reg_nav_treatment');
            $table->dropColumn('is_reg_nav_at_home');
            $table->dropColumn('is_reg_nav_storage');
            $table->dropColumn('is_reg_nav_expense');
            $table->dropColumn('is_reg_nav_report');    

        });
    }
}
