<?php

use Illuminate\Database\Seeder;

class ConfiguracionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('configuracion')->insert([
			[
				'coordinador_abasto'	            => 'DR. LUIS JOSÉ MANCILLA VELAZQUEZ',
				'director_atencion_medica'	        => 'DRA. LETICIA GUADALUPE MONTOYA LIÉVANO',
				'jefe_recursos_materiales'	        => 'LIC. GABRIEL FLORES CANCINO',
				'subdirector_recursos_materiales'	=> 'C.P. EDUARDO HERNANDEZ AMADOR',
				'director_administracion_finanzas'	=> 'LIC. JAIRO CESAR GUILLEN RAMIREZ'
			]
        ]);
    }
}
