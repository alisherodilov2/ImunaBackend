<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringDoctorPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referring_doctor_pays', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('kounteragent_doctor_contribution_price')->nullable(); // direktor id
            $table->string('date')->nullable(); // direktor id
            $table->string('kounteragent_contribution_price')->nullable(); // direktor id
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('counterparty_id')->nullable(); // direktor id
            $table->foreign('counterparty_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('referring_doctor_pays');
    }
}
