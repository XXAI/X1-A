<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActualizarTablaActasFechasValidacion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->timestamp('fecha_validacion')->after('estatus')->nullable();
            $table->timestamp('fecha_termino')->after('estatus')->nullable();
            $table->timestamp('fecha_importacion')->after('estatus')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->date('fecha_pedido')->after('fecha_solicitud')->nullable();
            $table->integer('num_oficio_pedido')->after('num_oficio')->length(10)->nullable();
            $table->string('fuente_financiamiento',255)->after('lugar_entrega')->nullable();
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
            $table->dropColumn('fecha_importacion');
            $table->dropColumn('fecha_validacion');
            $table->dropColumn('fecha_termino');
            $table->dropColumn('fecha_pedido');
            $table->dropColumn('num_oficio_pedido');
            $table->dropColumn('fuente_financiamiento');
        });
    }
}
