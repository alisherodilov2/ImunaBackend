<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraphArchiveItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('graph_archive_items', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('agreement_date')->nullable(); // kelishuv sanisi
            $table->string('agreement_time')->nullable(); // kelishuv vaqti
            $table->bigInteger('client_id')->nullable(); // kelishuv vaqti
            $table->bigInteger('graph_item_id')->nullable(); // kelishuv vaqti
            $table->unsignedInteger('graph_archive_id')->nullable();
            $table->foreign('graph_archive_id')
                ->references('id')
                ->on('graph_archives')
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
        Schema::dropIfExists('graph_archive_items');
    }
}
