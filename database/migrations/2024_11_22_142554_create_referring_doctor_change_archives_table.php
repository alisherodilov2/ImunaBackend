<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringDoctorChangeArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referring_doctor_change_archives', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('client_id')->nullable();
            $table->foreign('client_id') // Shortened constraint name
                  ->references('id')
                  ->on('clients')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->bigInteger('from_referring_doctor_id')->nullable();
            $table->bigInteger('to_referring_doctor_id')->nullable();
        
        
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
        Schema::dropIfExists('referring_doctor_change_archives');
    }
}
