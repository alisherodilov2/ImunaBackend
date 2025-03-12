<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaboratoryTemplateResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laboratory_template_results', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->text('name')->nullable();
            $table->boolean('is_print')->nullable();
            $table->text('result')->nullable();
            $table->text('normal')->nullable();
            $table->text('extra_column_1')->nullable();
            $table->text('extra_column_2')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('service_id')->default(null); // direktor id
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('laboratory_template_id')->default(null); // direktor id
            $table->foreign('laboratory_template_id')
                ->references('id')
                ->on('laboratory_templates')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('client_id')->default(null); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->bigInteger('client_value_id')->default(null); // direktor id
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
        Schema::dropIfExists('laboratory_template_results');
    }
}
