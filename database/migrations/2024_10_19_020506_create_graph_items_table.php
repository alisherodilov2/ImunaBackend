<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraphItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('graph_items', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('agreement_date')->nullable(); // kelishuv sanisi
            $table->string('agreement_time')->nullable(); // kelishuv vaqti
            $table->string('is_active')->nullable(); //  registerga otsa false bolib qoladi
            $table->string('is_arrived')->nullable(); //  keldi
            $table->unsignedInteger('department_id')->nullable(null); // direktor id
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('graph_id')->nullable();
            $table->foreign('graph_id')
                ->references('id')
                ->on('graphs')
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
        Schema::dropIfExists('graph_items');
    }
}
