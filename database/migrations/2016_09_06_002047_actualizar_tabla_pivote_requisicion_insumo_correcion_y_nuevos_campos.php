<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaPivoteRequisicionInsumoCorrecionYNuevosCampos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->renameColumn('cantidad_aprovada', 'cantidad_validada');
            $table->renameColumn('total_aprovado', 'total_validado');

            $table->integer('cantidad_recibida')->length(10)->nullable();
            $table->decimal('total_recibido',15,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisicion_insumo', function (Blueprint $table) {
            $table->renameColumn('cantidad_validada', 'cantidad_aprovada');
            $table->renameColumn('total_validado', 'total_aprovado');
            
            $table->dropColumn('cantidad_recibida');
            $table->dropColumn('total_recibido');
        });
    }
}
