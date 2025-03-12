<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_times', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->boolean('is_active')->nullable(); // kelishuv vaqti
            $table->string('agreement_time')->nullable(); // kelishuv vaqti
            $table->unsignedInteger('department_id')->nullable(null); // direktor id
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
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
        Schema::dropIfExists('client_times');
    }
}
