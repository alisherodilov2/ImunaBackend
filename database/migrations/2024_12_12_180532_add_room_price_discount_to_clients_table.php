<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoomPriceDiscountToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('statsionar_room_price')->nullable();
            $table->bigInteger('statsionar_room_qty')->nullable();
            $table->string('statsionar_room_discount')->nullable();
            $table->string('statsionar_room_price_pay')->nullable();
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
            $table->dropColumn('statsionar_room_price');
            $table->dropColumn('statsionar_room_qty');
            $table->dropColumn('statsionar_room_discount');
            $table->dropColumn('statsionar_room_price_pay');

        });
    }
}
