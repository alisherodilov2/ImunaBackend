<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCounterpartySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('counterparty_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('treatment_plan_qty')->nullable();
            $table->string('ambulatory_plan_qty')->nullable();
            $table->string('treatment_service_price')->nullable();
            $table->string('treatment_service_kounteragent_price')->nullable();
            $table->string('ambulatory_service_price')->nullable();
            $table->string('ambulatory_service_kounteragent_price')->nullable();
            $table->unsignedInteger('counterparty_id')->nullable(); // direktor id
            $table->foreign('counterparty_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('user_id')->nullable(); // direktor id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('treatment_service_id')->nullable(); // direktor id
            $table->foreign('treatment_service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
            $table->unsignedInteger('ambulatory_service_id')->nullable(); // direktor id
            $table->foreign('ambulatory_service_id')
                ->references('id')
                ->on('services')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->nullable();
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
        Schema::dropIfExists('counterparty_settings');
    }
}
