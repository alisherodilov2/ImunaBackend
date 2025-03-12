<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReletionToPersonDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('graphs', function (Blueprint $table) {
            $table->string('person_id')->nullable(); // bir marta karta
            $table->string('sex')->nullable(); /// jinsi
            $table->string('data_birth')->nullable(); // tugilgan sanasi
            $table->string('citizenship')->nullable(); /// fuqaroligi
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('graphs', function (Blueprint $table) {
            $table->dropColumn('person_id')->nullable(); // Chegirma
            $table->dropColumn('sex')->nullable(); // Chegirma
            $table->dropColumn('data_birth')->nullable(); // Chegirma
            $table->dropColumn('citizenship')->nullable(); // Chegirma
        });
    }
}
