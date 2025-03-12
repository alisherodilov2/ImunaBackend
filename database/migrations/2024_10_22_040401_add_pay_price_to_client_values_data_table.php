<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayPriceToClientValuesDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_values', function (Blueprint $table) {
            $table->string('pay_price')->nullable(); /// fuqaroligi

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
            $table->dropColumn('pay_price')->nullable(); // Chegirma
        });
    }
}
