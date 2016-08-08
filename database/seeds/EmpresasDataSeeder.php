<?php

use Illuminate\Database\Seeder;

class EmpresasDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('empresas')->insert([
			[
				'clave' 				=> 'disur',
				'nombre' 				=> 'DISTRIBUIDORA DISUR, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0001',
				'partida_presupuestal' 	=> '25301.- MEDICINAS Y PRODUCTOS FARMACÉUTICOS'
			],
			[
				'clave' 				=> 'disur',
				'nombre' 				=> 'DISTRIBUIDORA DISUR, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0002',
				'partida_presupuestal' 	=> '25301.- MEDICINAS Y PRODUCTOS FARMACÉUTICOS'
			],
			[
				'clave' 				=> 'disur',
				'nombre' 				=> 'DISTRIBUIDORA DISUR, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0003',
				'partida_presupuestal' 	=> '25401.- MATERIALES, ACCESORIOS  Y SUMINISTROS MEDICOS'
			],
			[
				'clave' 				=> 'exfarma',
				'nombre' 				=> 'EXFARMA, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0005',
				'partida_presupuestal' 	=> '25301.- MEDICINAS Y PRODUCTOS FARMACÉUTICOS'
			],
			[
				'clave' 				=> 'exfarma',
				'nombre' 				=> 'EXFARMA, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0006',
				'partida_presupuestal' 	=> '25301.- MEDICINAS Y PRODUCTOS FARMACÉUTICOS'
			],
			[
				'clave' 				=> 'exfarma',
				'nombre' 				=> 'EXFARMA, S.A. DE C.V.',
				'pedido' 				=> 'P-16-0007',
				'partida_presupuestal' 	=> '25401.- MATERIALES, ACCESORIOS  Y SUMINISTROS MEDICOS'
			]
        ]);
    }
}
