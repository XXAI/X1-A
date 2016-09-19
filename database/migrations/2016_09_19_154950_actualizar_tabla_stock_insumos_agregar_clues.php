<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaStockInsumosAgregarClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_insumos', function (Blueprint $table) {
            $table->string('clues',12)->after('entrega_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_insumos', function (Blueprint $table) {
            $table->dropColumn('clues');
        });
    }
}
