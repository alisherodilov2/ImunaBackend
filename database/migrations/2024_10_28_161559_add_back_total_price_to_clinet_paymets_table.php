<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackTotalPriceToClinetPaymetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clinet_paymets', function (Blueprint $table) {
            $table->string('back_total_price')->nullable();///qaytarilgan pul
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clinet_paymets', function (Blueprint $table) {
            $table->dropColumn('back_total_price');
        });
    }
}
