<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->bigInteger('doctor_id')->nullable(); // direktor id
            $table->longText('contribution_data')->nullable(); // direktor id
            $table->bigInteger('service_count')->nullable(); // direktor id
            $table->bigInteger('department_id')->nullable(); // direktor id
            $table->unsignedInteger('client_id')->nullable(); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->string('total_price')->nullable(); //Doktor ulushi
            $table->string('doctor_contribution_price_pay')->nullable(); //Doktor ulushi
            $table->string('total_doctor_contribution_price')->nullable(); //Doktor ulushi
            $table->string('date')->nullable();
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
        Schema::dropIfExists('doctor_balances');
    }
}
