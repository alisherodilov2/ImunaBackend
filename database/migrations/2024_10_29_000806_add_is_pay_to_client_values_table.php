<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPayToClientValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_values', function (Blueprint $table) {
            $table->boolean('is_pay')->nullable();///qaytarilgan pul
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_values', function (Blueprint $table) {
            $table->dropColumn('is_pay');
        });
    }
}
