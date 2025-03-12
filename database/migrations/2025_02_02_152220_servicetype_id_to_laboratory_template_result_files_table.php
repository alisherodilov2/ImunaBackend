<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServicetypeIdToLaboratoryTemplateResultFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laboratory_template_result_files', function (Blueprint $table) {
            $table->bigInteger('servicetype_id')->nullable();
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laboratory_template_result_files', function (Blueprint $table) {
            $table->dropColumn('servicetype_id');
        });
    }
}
