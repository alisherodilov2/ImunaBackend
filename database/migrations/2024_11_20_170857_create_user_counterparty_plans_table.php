<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCounterpartyPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_counterparty_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('status')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // 
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('service_id')->default(null); // direktor id
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
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
        Schema::dropIfExists('user_counterparty_plans');
    }
}
