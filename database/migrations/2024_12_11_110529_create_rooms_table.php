<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('type')->nullable(); // Xona turi
            $table->string('number')->nullable(); // Xona raqami
            $table->string('room_index')->nullable(); // O'rin raqami
            $table->string('price')->nullable(); // Narxi
            $table->string('doctor_contribution')->nullable(); // Shifokor ulushi
            $table->string('nurse_contribution')->nullable(); //Hamshira ulushi
            $table->boolean('is_empty')->nullable(); //Hamshira ulushi
            $table->bigInteger('client_id')->nullable(); // Mijoz id
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
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
        Schema::dropIfExists('rooms');
    }
}
