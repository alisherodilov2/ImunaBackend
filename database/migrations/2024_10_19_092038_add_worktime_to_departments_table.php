<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorktimeToDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('work_start_time')->nullable(); //  tolov qilinsa 
            $table->string('work_end_time')->nullable(); //  tolov qilinsa 
            $table->longText('working_days')->nullable(); //  tolov qilinsa 
            $table->string('duration')->nullable(); // Chegirma

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('work_start_time')->nullable(); // Chegirma
            $table->dropColumn('work_end_time')->nullable(); // Chegirma
            $table->dropColumn('working_days')->nullable(); // Chegirma
            $table->dropColumn('duration')->nullable(); // Chegirma
        });
    }
}
