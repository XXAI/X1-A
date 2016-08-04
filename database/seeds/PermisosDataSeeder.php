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
	            'clave' => 'DASHBOARD',
	            'descripcion' => 'Permite el acceso al Dashboard del sistema.',
	            'grupo' => 'GENERAL'
			],
			[
	            'clave' => 'LISTAR_USUARIOS',
	            'descripcion' => 'Listar los usuarios registrados en el sistema.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => 'LISTAR_ROLES',
	            'descripcion' => 'Listar los roles creados.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => 'ADMIN_USUARIOS',
	            'descripcion' => 'Permite agregar/editar/eliminar nuevos usuarios para el sistema.',
	            'grupo' => 'ADMIN'
			],
			[
	            'clave' => 'ADMIN_ROLES',
	            'descripcion' => 'Permite crear/editar/eliminar nuevos roles del sistema.',
	            'grupo' => 'ADMIN'
			]
        ]);
    }
}