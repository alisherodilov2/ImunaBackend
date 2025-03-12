<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalPriceToClintPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clinet_paymets', function (Blueprint $table) {
            $table->string('total_price')->nullable(); // To'langan
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
            $table->dropColumn('total_price')->nullable(); // Chegirma
        });
    }
}
