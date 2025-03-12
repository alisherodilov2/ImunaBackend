<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientResultIdToResultTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('result_templates', function (Blueprint $table) {
            $table->bigInteger('client_result_id')->nullable(); // direktor id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('result_templates', function (Blueprint $table) {
            $table->dropColumn('client_result_id');
        });
    }
}
