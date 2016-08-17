<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivoteActaProveedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acta_proveedor', function (Blueprint $table) {
            $table->integer('acta_id')->length(10)->unsigned();
            $table->integer('proveedor_id')->length(10)->unsigned();
            
            $table->integer('num_oficio')->length(10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acta_proveedor');
    }
}
