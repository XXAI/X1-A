<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaConfiguracion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre_solitante_requisicion',255)->nullable();
            $table->string('cargo_solitante_requisicion',255)->nullable();
            $table->string('director_atencion_medica',255);
            $table->string('jefe_recursos_materiales',255);
            $table->string('subdirector_recursos_materiales',255);
            $table->string('director_administracion_finanzas',255);
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
        Schema::drop('configuracion');
    }
}
