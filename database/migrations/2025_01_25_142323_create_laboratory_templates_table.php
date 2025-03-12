<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaboratoryTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laboratory_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->text('name')->nullable();
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
        Schema::dropIfExists('laboratory_templates');
    }
}
