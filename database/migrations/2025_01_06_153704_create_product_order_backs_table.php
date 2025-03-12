<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOrderBacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_order_backs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('qty')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('pharmacy_id')->nullable(); // direktor id
            $table->foreign('pharmacy_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('pharmacy_product_id')->nullable(); // direktor id
            $table->foreign('pharmacy_product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('product_reception_item_id')->nullable(); // direktor id
            $table->foreign('product_reception_item_id')
                ->references('id')
                ->on('product_reception_items')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('product_id')->nullable(); // direktor id
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
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
        Schema::dropIfExists('product_order_backs');
    }
}
