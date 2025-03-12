<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PassNumberBlankToDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->boolean('is_reg_pass_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->dropColumn('is_reg_pass_number');
        });
    }
}
