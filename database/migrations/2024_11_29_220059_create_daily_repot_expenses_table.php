<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyRepotExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_repot_expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('daily_repot_id')->nullable();
            $table->foreign('daily_repot_id')
                ->references('id')
                ->on('daily_repots')
                ->onDelete('cascade');
        
            $table->bigInteger('expense_id')->nullable();
            
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
        Schema::dropIfExists('daily_repot_expenses');
    }
}
