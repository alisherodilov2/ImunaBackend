<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_values', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            // $table->bigInteger('service_id')->default(null); // direktor id
            $table->unsignedInteger('client_id')->default(null); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('service_id')->default(null); // direktor id
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->string('price')->nullable(); // narxi
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
        Schema::dropIfExists('client_values');
    }
}
