<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaActasAgregarIndexClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->index('clues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropIndex(['clues']);
        });
    }
}
