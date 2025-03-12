<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * 
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('user_id')->default(null); // direktor id
            $table->string('name')->nullable(); //Bo'lim nomi
            $table->string('floor')->nullable(); //Bo'lim qavati
            $table->string('main_room')->nullable(); //Asosiy xona
            $table->string('letter')->nullable(); //Harf
            $table->boolean('probirka')->nullable(); //Probirka

            $table->bigInteger('parent_id')->nullable(); //Xona nomeri
            $table->string('room_number')->nullable(); //Xona nomeri
            $table->string('room_type')->nullable(); //Xona turi
            $table->timestamps();
            $table->foreign('user_id')
            ->references('id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('departments');
    }
}
