<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolUsuarioPivotTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('rol_usuario', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('rol_id')->unsigned();
			$table->integer('usuario_id')->unsigned();

			$table->foreign('usuario_id')
                  ->references('id')->on('usuarios')
                  ->onDelete('cascade');
			$table->foreign('rol_id')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('rol_usuario');
	}

}
