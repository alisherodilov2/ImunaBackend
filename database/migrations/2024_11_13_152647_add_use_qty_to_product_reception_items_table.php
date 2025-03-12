<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseQtyToProductReceptionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_reception_items', function (Blueprint $table) {
            $table->string('use_qty')->nullable(); //soni
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_reception_items', function (Blueprint $table) {
            $table->dropColumn('use_qty'); //soni
        });
    }
}
