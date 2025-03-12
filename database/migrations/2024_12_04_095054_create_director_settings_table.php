<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('director_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->boolean('is_reg_monoblok')->nullable();
            $table->boolean('is_reg_data_birth')->nullable();
            $table->boolean('is_reg_phone')->nullable();
            $table->boolean('is_reg_sex')->nullable();
         
            $table->unsignedInteger('user_id')->nullable();
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
        Schema::dropIfExists('director_settings');
    }
}
