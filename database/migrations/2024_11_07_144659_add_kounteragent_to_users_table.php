<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKounteragentToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('treatment_service_id')->nullable();
            $table->string('treatment_plan_qty')->nullable();
            $table->bigInteger('ambulatory_service_id')->nullable();
            $table->string('ambulatory_plan_qty')->nullable();
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
            $table->dropColumn('treatment_service_id');
            $table->dropColumn('treatment_plan_qty');
            $table->dropColumn('ambulatory_service_id');
            $table->dropColumn('ambulatory_plan_qty');
        });
    }
}
