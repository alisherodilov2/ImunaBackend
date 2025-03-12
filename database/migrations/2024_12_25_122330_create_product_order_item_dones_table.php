<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOrderItemDonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_order_item_dones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('qty')->nullable();
            $table->string('expiration_date')->nullable();
            $table->unsignedInteger('product_id')->nullable(); // direktor id
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_order_item_id')->nullable(); // direktor id
            $table->foreign('product_order_item_id')
                ->references('id')
                ->on('product_order_items')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('pharmacy_product_id')->nullable(); // direktor id
            $table->foreign('pharmacy_product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_order_item_dones');
    }
}
