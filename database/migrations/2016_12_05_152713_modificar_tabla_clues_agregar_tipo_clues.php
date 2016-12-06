<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModificarTablaCluesAgregarTipoClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clues', function (Blueprint $table) {
            $table->integer('tipo_clues')->length(2)->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clues', function (Blueprint $table) {
            $table->dropColumn('tipo_clues');
        });
    }
}
