<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referring_doctors', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('first_name')->nullable(); // ismi
            $table->string('last_name')->nullable(); // familiyasi
            $table->string('workplace')->nullable(); // ata ismi
            $table->string('phone')->nullable(); // telefon raqami
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
        Schema::dropIfExists('referring_doctors');
    }
}
