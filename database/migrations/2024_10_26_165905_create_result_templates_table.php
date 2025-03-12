<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('status')->nullable();
            $table->string('value')->nullable();
            $table->unsignedInteger('client_id')->default(null); // direktor id
            $table->unsignedInteger('template_id')->default(null); // direktor id
            $table->bigInteger('template_item_id')->nullable(); // direktor id
            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->onDelete('cascade');
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
      
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
        Schema::dropIfExists('result_templates');
    }
}
