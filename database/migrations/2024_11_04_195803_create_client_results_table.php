<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_results', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('duration')->nullable(); ///vaqti
            $table->string('start_time')->nullable(); ///boshlagan soati
            $table->string('is_check_doctor')->nullable(); ///holati
            $table->string('use_duration')->nullable(); ///holati

            $table->unsignedInteger('room_id')->nullable(); // direktor id
            $table->foreign('room_id')
            ->references('id')
            ->on('departments')
            ->onDelete('cascade');
            $table->unsignedInteger('department_id')->nullable(); // direktor id
            $table->foreign('department_id')
            ->references('id')
            ->on('departments')
            ->onDelete('cascade');
            $table->bigInteger('client_id')->nullable(); // direktor id
            $table->unsignedInteger('doctor_id')->nullable(); // direktor id
            $table->foreign('doctor_id')
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
        Schema::dropIfExists('client_results');
    }
}
