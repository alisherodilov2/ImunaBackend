<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentIdToDepartmentIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_values', function (Blueprint $table) {
            $table->bigInteger('department_id')->default(null); // direktor id
            $table->bigInteger('queue_number')->nullable(); // To'langan
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_values', function (Blueprint $table) {
            $table->bigInteger('department_id')->nullable(); // Chegirma
            $table->bigInteger('queue_number')->nullable(); // Chegirma
        });
    }
}
