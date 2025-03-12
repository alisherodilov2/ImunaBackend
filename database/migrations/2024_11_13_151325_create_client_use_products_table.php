ef<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientUseProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_use_products', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('qty')->nullable(); //soni
            $table->unsignedInteger('client_id')->nullable(); // 
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('client_value_id')->nullable(); // 
            $table->bigInteger('product_id')->nullable(); // 
            $table->bigInteger('product_category_id')->nullable(); // 
            $table->unsignedInteger('service_id')->nullable(); // xizmat
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_reception_item_id')->nullable(); // qabul qilinganlar
            $table->foreign('product_reception_item_id')
                ->references('id')
                ->on('product_reception_items')
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
        Schema::dropIfExists('client_use_products');
    }
}
