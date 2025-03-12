<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->unsignedInteger('user_id')->default(null); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('department_id')->default(null); // direktor id
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('servicetype_id')->default(null); // direktor id
            $table->foreign('servicetype_id')
                ->references('id')
                ->on('servicetypes')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->text('name')->nullable(); //Xizmat nomi
            $table->string('price')->nullable(); //Narxi
            $table->string('doctor_contribution_price')->nullable(); //Doktor ulushi
            $table->string('kounteragent_contribution_price')->nullable(); //Kounteragent ulushi
            $table->string('kounteragent_doctor_contribution_price')->nullable(); //Kounterdoktor ulushi
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
        Schema::dropIfExists('services');
    }
}
