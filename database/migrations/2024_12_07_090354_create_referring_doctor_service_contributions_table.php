<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringDoctorServiceContributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referring_doctor_service_contributions', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('service_id')->nullable(); // direktor id
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();

            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();

            $table->unsignedInteger('ref_doc_id')->nullable(); // direktor id
            $table->foreign('ref_doc_id')
                ->references('id')
                ->on('referring_doctors')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();

            $table->string('contribution_price')->nullable(); //Doktor ulushi
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
        Schema::dropIfExists('referring_doctor_service_contributions');
    }
}
