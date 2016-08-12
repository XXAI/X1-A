<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Model::unguard();

        $this->call(PermisosDataSeeder::class);
        $this->call(RolesDataSeeder::class);
	    $this->call(UsuariosDataSeeder::class);
	    $this->call(EmpresasDataSeeder::class);
	    $this->call(ConfiguracionDataSeeder::class);
	    $this->call(InsumosSeeder::class);
	    
	    Model::reguard();
	}

}
