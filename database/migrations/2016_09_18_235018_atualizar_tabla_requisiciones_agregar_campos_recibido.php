<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AtualizarTablaRequisicionesAgregarCamposRecibido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            $table->decimal('iva_recibido',15,2)->after('iva_validado')->nullable();
            $table->decimal('gran_total_recibido',15,2)->after('iva_validado')->nullable();
            $table->decimal('sub_total_recibido',15,2)->after('iva_validado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            $table->dropColumn('iva_recibido');
            $table->dropColumn('gran_total_recibido');
            $table->dropColumn('sub_total_recibido');
        });
    }
}
