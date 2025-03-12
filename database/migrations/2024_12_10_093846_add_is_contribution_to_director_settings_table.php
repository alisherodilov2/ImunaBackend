<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsContributionToDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->boolean('is_contribution_doc')->nullable();
            $table->boolean('is_contribution_kounteragent')->nullable();
            $table->boolean('is_contribution_kt_doc')->nullable();
            $table->boolean('is_chek_rectangle')->nullable();
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
            $table->dropColumn('is_contribution_doc');
            $table->dropColumn('is_contribution_kounteragent');
            $table->dropColumn('is_contribution_kt_doc');
            $table->dropColumn('is_chek_rectangle');
        });
    }
}
