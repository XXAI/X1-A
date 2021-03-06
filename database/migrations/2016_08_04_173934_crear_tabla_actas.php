<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaActas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clues',12);
            $table->integer('numero')->length(10)->nullable();
            $table->string('folio',100);
            $table->integer('num_oficio')->length(10)->nullable();
            $table->string('ciudad',255);
            $table->date('fecha');
            $table->date('fecha_solicitud')->nullable();
            $table->time('hora_inicio');
            $table->time('hora_termino');
            $table->string('lugar_reunion',255);
            $table->string('lugar_entrega',255)->nullable();
            $table->string('empresa_clave',45);

            $table->string('director_unidad',255)->nullable();
            $table->string('administrador',255)->nullable();
            $table->string('encargado_almacen',255)->nullable();
            $table->string('coordinador_comision_abasto',255)->nullable();

            //$table->string('firma_solicita',255);
            //$table->string('cargo_solicita',255);

            $table->integer('estatus')->length(1);
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
        Schema::drop('actas');
    }
}
