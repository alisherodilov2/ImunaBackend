<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPharmacyProductIdToProductReceptionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_reception_items', function (Blueprint $table) {
            $table->bigInteger('pharmacy_product_id')->nullable();
            $table->bigInteger('product_order_item_id')->nullable(); // direktor id
            $table->bigInteger('product_order_id')->nullable(); // direktor id
            $table->bigInteger('product_order_item_done_id')->nullable(); // direktor id
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
            $table->dropColumn('pharmacy_product_id');
            $table->dropColumn('product_order_item_id');
            $table->dropColumn('product_order_id');
            $table->dropColumn('product_order_item_done_id');
        });
    }
}
