<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChekToDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('director_settings', function (Blueprint $table) {
            $table->boolean('is_qr_chek')->nullable();
            $table->boolean('is_logo_chek')->nullable();
            $table->string('logo_width')->nullable();
            $table->text('domain')->nullable();
            $table->string('logo_height')->nullable();
            $table->text('result_domain')->nullable();
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
            $table->dropColumn('is_logo_chek');
            $table->dropColumn('is_qr_chek');
            $table->dropColumn('logo_width');
            $table->dropColumn('logo_height');
            $table->dropColumn('domain');
            $table->dropColumn('result_domain');
        });
    }
}
