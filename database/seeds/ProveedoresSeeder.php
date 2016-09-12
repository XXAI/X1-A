<?php

use Illuminate\Database\Seeder;

class ProveedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('proveedores')->insert([
			[
				'nombre'			=> 'Distribuidora Médica del Soconusco S.A. de C.V.',
				'direccion'			=> '4ª. Poniente Sur No. 360',
				'ciudad'			=> 'Tuxtla Gutiérrez, Chiapas',
				'contacto'			=> 'C.P. Francisco Javier Velasco Álvarez',
				'cargo_contacto'	=> 'Gerente General',
				'telefono'			=> '61-3-34-00',
				'cel'				=> '961-579-2940',
				'email'				=> 'javier.velasco@grupodms.com.mx'
			],
			[
				'nombre'			=> 'Distribuidora Médica de los Ángeles',
				'direccion'			=> '1ª. Poniente Sur No. 442 CP. 29050 Col. Terán',
				'ciudad'			=> 'Tuxtla Gutiérrez, Chiapas',
				'contacto'			=> 'C.P. Laura García Ochoa',
				'cargo_contacto'	=> 'Gerente General',
				'telefono'			=> '21-2-18-37',
				'cel'				=> '961-579-2659',
				'email'				=> null
			],
			[
				'nombre'			=> 'Farmacias BIOS',
				'direccion'			=> 'Av. Cuauhtémoc No. 9, Col. Centro CP. 29200',
				'ciudad'			=> 'San Cristóbal de las Casas, Chiapas',
				'contacto'			=> 'C.P. José Manuel Hernández Escobar',
				'cargo_contacto'	=> 'Gerente General',
				'telefono'			=> '967-67-8-83-78',
				'cel'				=> '967-102-0217',
				'email'				=> 'gerenciabios@hotmail.com'
			],
			[
				'nombre'			=> 'Distribuidora  de Insumos Médicos SAHU S.A. de C.V.',
				'direccion'			=> 'Av. Oaxaca 330 Guanajuato y Taxco Residencial Hacienda',
				'ciudad'			=> 'Tuxtla Gutiérrez, Chiapas',
				'contacto'			=> 'LAE. Carlos Alberto Silva Rivera',
				'cargo_contacto'	=> 'Gerente Comercial',
				'telefono'			=> null,
				'cel'				=> '961-262-9741',
				'email'				=> 'Csilva_rivera@hotmail.com'
			],
			[
				'nombre'			=> 'Comercializadora Quirúrgicas y Hospitalarias S.A. de C.V.',
				'direccion'			=> '9ª Poniente Sur 281, Col. Canoitas CP 29000',
				'ciudad'			=> 'Tuxtla Gutiérrez, Chiapas',
				'contacto'			=> 'QFB. Miguel Ángel Blas Gutiérrez',
				'cargo_contacto'	=> 'Gerente General',
				'telefono'			=> '61-11-18-43',
				'cel'				=> '961-295-1884',
				'email'				=> 'Sumedic_tuxtla@hotmail.com'
			],
			[
				'nombre'			=> 'Equipos Médicos de Chiapas',
				'direccion'			=> 'Calle Burocrática entre 12ª y libramiento Sur No. 1337 Colonia Burocrática',
				'ciudad'			=> 'Tuxtla Gutiérrez, Chiapas',
				'contacto'			=> 'José Alberto Zarate Rodríguez',
				'cargo_contacto'	=> null,
				'telefono'			=> null,
				'cel'				=> '961-602-9952',
				'email'				=> null
			]
        ]);
    }
}
