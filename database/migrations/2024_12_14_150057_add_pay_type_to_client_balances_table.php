<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayTypeToClientBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_balances', function (Blueprint $table) {
            $table->string('pay_type')->nullable();
            $table->bigInteger('daily_repot_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_balances', function (Blueprint $table) {
            $table->dropColumn('pay_type');
            $table->dropColumn('daily_repot_id');
        });
    }
}
