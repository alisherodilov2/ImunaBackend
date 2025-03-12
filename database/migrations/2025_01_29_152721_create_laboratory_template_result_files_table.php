<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaboratoryTemplateResultFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laboratory_template_result_files', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('type')->nullable();
            $table->string('file')->nullable();
            $table->text('name')->nullable();
            $table->unsignedInteger('client_id')->default(null); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
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
        Schema::dropIfExists('laboratory_template_result_files');
    }
}
