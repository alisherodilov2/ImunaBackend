<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinetPaymetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinet_paymets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->default(null); // direktor id
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
            $table->string('pay_type')->nullable(); //  To'langan
            $table->string('cash_price')->nullable(); // naqd
            $table->string('card_price')->nullable(); // naqd
            $table->string('transfer_price')->nullable(); // otkazma
            $table->string('pay_total_price')->nullable(); // To'langan
            $table->string('discount')->nullable(); // chegirma
            $table->string('discount_comment')->nullable(); // chegirma izohi
            $table->string('debt_price')->nullable(); //qarz
            $table->string('debt_comment')->nullable(); // qarz izohi



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
        Schema::dropIfExists('clinet_paymets');
    }
}
