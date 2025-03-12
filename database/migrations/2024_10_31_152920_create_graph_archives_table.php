<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraphArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('graph_archives', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->bigInteger('graph_id')->nullable(); // grafik idisi
            $table->string('person_id')->nullable(); // bir marta karta
            $table->string('status')->nullable(); // holati
            $table->unsignedInteger('user_id')->nullable(); /// kim qoshgani
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('graph_archives');
    }
}
