<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_certificates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serial_number_1')->nullable();
            $table->string('serial_number_2')->nullable();
            $table->string('date_1')->nullable();
            $table->string('date_2')->nullable();
            $table->unsignedInteger('client_id')->nullable(); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('department_id')->nullable(); // direktor id
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('doctor_id')->nullable(); // direktor id
            $table->foreign('doctor_id')
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
        Schema::dropIfExists('client_certificates');
    }
}
