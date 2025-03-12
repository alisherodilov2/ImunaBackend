<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringDoctorBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referring_doctor_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('referring_doctor_id')->nullable(); // direktor id
            $table->foreign('referring_doctor_id')
                ->references('id')
                ->on('referring_doctors')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('client_id')->nullable(); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->string('total_price')->nullable(); //Doktor ulushi
            $table->string('total_doctor_contribution_price')->nullable(); //Doktor ulushi
            $table->string('total_kounteragent_contribution_price')->nullable(); //Kounteragent ulushi
            $table->string('total_kounteragent_doctor_contribution_price')->nullable(); //Kounterdoktor ulushi //
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
        Schema::dropIfExists('referring_doctor_balances');
    }
}
