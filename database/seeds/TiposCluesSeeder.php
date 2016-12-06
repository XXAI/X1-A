<?php

use Illuminate\Database\Seeder;

class TiposCluesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tipos_clues')->insert([
			[
				'id'				=> 1,
				'nombre'			=> 'Unidades',
				'descripcion'		=> 'ATENCION MENTAL,BANCO DE SANGRE,CASA DE SALUD,CENTRO DE SALUD,CENTRO DE SALUD - SERVICIOS AMPLIADOS,CLINICA DE ESPECIALIDADES,LABORATORIO,UNIDAD MEDICA',
			],
			[
				'id'				=> 2,
				'nombre'			=> 'Caravanas',
				'descripcion'		=> 'CARAVANA',
			],
			[
				'id'				=> 3,
				'nombre'			=> 'Jurisdicciones',
				'descripcion'		=> 'ALMACEN,OFICINAS (JURISDICCIONES)',
			],
			[
				'id'				=> 4,
				'nombre'			=> 'Coordinadores de Caravanas',
				'descripcion'		=> 'COORDINADOR DE CARAVANAS',
			],
			[
				'id'				=> 5,
				'nombre'			=> 'Hospitales',
				'descripcion'		=> 'HOPITAL GENERAL, HOSPITAL',
			]
        ]);
    }
}
