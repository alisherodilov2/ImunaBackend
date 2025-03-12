<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductReceptionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_reception_items', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('price')->nullable();
            $table->string('qty')->nullable();
            $table->string('manufacture_date')->nullable();
            $table->string('expiration_date')->nullable();
            $table->unsignedInteger('product_id')->nullable(); // direktor id
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_category_id')->nullable(); // direktor id
            $table->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_reception_id')->nullable(); // direktor id
            $table->foreign('product_reception_id')
                ->references('id')
                ->on('product_receptions')
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
        Schema::dropIfExists('product_reception_items');
    }
}
