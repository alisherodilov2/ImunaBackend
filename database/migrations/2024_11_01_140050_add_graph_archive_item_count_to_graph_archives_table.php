<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGraphArchiveItemCountToGraphArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('graph_archives', function (Blueprint $table) {
            $table->bigInteger('graph_archive_item_count')->nullable();
            $table->bigInteger('came_graph_archive_item_count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('graph_archives', function (Blueprint $table) {
            $table->dropColumn('graph_archive_item_count');
            $table->dropColumn('came_graph_archive_item_count');
        });
    }
}
