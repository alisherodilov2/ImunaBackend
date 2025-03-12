<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name')->nullable(); // ismi
            $table->string('last_name')->nullable(); // familiyasi
            $table->string('parent_name')->nullable(); // ata ismi
            $table->string('data_birth')->nullable(); // tugilgan sanasi
            $table->string('citizenship')->nullable(); /// fuqaroligi
            $table->string('phone')->nullable(); // telefon raqami
            $table->string('sex')->nullable(); /// jinsi
            $table->string('price')->nullable(); // narxi
            $table->string('person_id')->nullable(); // bir marta karta
            $table->bigInteger('parent_id')->nullable();
            $table->unsignedInteger('user_id')->default(null); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->softDeletes();
          
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
        Schema::dropIfExists('clients');
    }
}
