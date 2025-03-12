<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialExpenseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_expense_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('qty')->nullable();
            $table->unsignedInteger('product_id')->nullable(); // 
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('material_expense_id')->nullable(); // 
            $table->foreign('material_expense_id')
                ->references('id')
                ->on('material_expenses')
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
        Schema::dropIfExists('material_expense_items');
    }
}
