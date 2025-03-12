<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClintValueIdDataToClintPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clinet_paymets', function (Blueprint $table) {
            $table->longText('client_value_id_data')->nullable(); // To'langan
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
            $table->dropColumn('client_value_id_data')->nullable(); // Chegirma
        });
    }
}
