<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyRepotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_repots', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            // clinet
            $table->string('cash_price')->nullable();
            $table->string('card_price')->nullable();
            $table->string('transfer_price')->nullable();
            $table->string('total_price')->nullable();
            // rasxondalr
            $table->string('give_cash_price')->nullable();
            $table->string('give_card_price')->nullable();
            $table->string('give_transfer_price')->nullable();
            $table->boolean('is_cash')->nullable();
            $table->boolean('is_card')->nullable();
            $table->boolean('is_transfer')->nullable();
            $table->bigInteger('batch_number')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('daily_repots');
    }
}
