<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use App\Rol as Rol;
use App\Permiso as Permiso;

class RolesDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$permisos = Permiso::lists('id');
        
    	$admin_rol = new Rol();
        $admin_rol->nombre = 'ADMIN';
        $admin_rol->save();

        $rol_id = $admin_rol->id;
        $relations = array();

        foreach ($permisos as $permiso_id) {
            $relations[] = [
                'permiso_id' => $permiso_id,
                'rol_id' => $rol_id
            ];
        }

        DB::table('permiso_rol')->insert($relations);
    }
}
