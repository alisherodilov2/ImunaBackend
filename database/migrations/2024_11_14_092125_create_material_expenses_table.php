<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('qty')->nullable();
            $table->unsignedInteger('product_id')->nullable(); // 
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->longText('comment')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // 
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('material_expenses');
    }
}
