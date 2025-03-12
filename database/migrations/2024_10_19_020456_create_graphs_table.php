<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraphsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('graphs')) {
            Schema::create('graphs', function (Blueprint $table) {
                $table->increments('id');
                $table->softDeletes();
                $table->string('first_name')->nullable(); // ismi
                $table->string('last_name')->nullable(); // familiyasi
                $table->string('phone')->nullable(); // telefon raqami
                $table->unsignedInteger('user_id')->default(null); // direktor id
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('graphs');
    }
}
