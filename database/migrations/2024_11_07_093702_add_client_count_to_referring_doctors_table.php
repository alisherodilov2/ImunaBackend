<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientCountToReferringDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referring_doctors', function (Blueprint $table) {
            $table->string('total_price')->nullable();
            $table->bigInteger('client_count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referring_doctors', function (Blueprint $table) {
            $table->dropColumn('total_price');
            $table->dropColumn('client_count');
        });
    }
}
