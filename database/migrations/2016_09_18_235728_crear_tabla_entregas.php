<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaEntregas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('acta_id')->length(10)->unsigned();
            $table->integer('proveedor_id')->length(10)->unsigned();
            $table->date('fecha_entrega')->nullable();
            $table->time('hora_entrega')->nullable();
            $table->date('fecha_proxima_entrega')->nullable();
            $table->string('nombre_recibe',225)->nullable();
            $table->string('nombre_entrega',225)->nullable();
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
        Schema::drop('entregas');
    }
}
