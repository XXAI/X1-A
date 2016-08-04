<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use App\Rol as Rol;
use App\Usuario as Usuario;

class UsuariosDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$rol = Rol::where('nombre','ADMIN')->first();

    	$usuario = new Usuario();
        $usuario->email = 'maca.15c@gmail.com';
        $usuario->save();

        $usuario->roles()->attach($rol->id);

        $usuario = new Usuario();
        $usuario->email = 'mruizromero@gmail.com';
        $usuario->save();

        $usuario->roles()->attach($rol->id);
    }
}
