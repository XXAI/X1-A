<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PermisosDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permisos')->insert([
			[
	            'clave' => '56C1E52B98B62',
	            'descripcion' => 'Listar los usuarios registrados en el sistema.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => '5CA553826561D',
	            'descripcion' => 'Listar los roles creados.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => 'EFDA3A4948D9E',
	            'descripcion' => 'Permite agregar/editar/eliminar nuevos usuarios para el sistema.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => 'FDB1C17AAF43A',
	            'descripcion' => 'Permite crear/editar/eliminar nuevos roles del sistema.',
	            'grupo' => 'ADMIN'
			]
        ]);
    }
}