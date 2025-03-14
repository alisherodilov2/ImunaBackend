<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAtHomeToGraphArchiveItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('graph_archive_items', function (Blueprint $table) {
            $table->boolean('is_at_home')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('graph_archive_items', function (Blueprint $table) {
            $table->dropColumn('is_at_home');
        });
    }
}
