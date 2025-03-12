<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDiscountPriceFromClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('clients', 'discount_price')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('discount_price'); // Ustun mavjud bo'lsa, olib tashlanadi
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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('discount_price')->nullable(); // Ustunni q
        });
    }
}
