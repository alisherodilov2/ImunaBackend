<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('duration')->nullable(); ///minuti
            $table->string('use_duration')->nullable();///ishlatkan vaqti
            $table->string('start_time')->nullable();///boshlagan vaqti/pauz qilsa va yana start bossa ozgaradi bu yer
            $table->string('is_check_doctor')->nullable();///tekshirib boldi /start/pause/finish
            $table->bigInteger('room_id')->nullable(); /// xona raqami
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('use_duration');
            $table->dropColumn('start_time');
            $table->dropColumn('is_check_doctor');
            $table->dropColumn('room_id');
        });
    }
}
