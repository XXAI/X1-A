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
    	$claves_permisos = array('56C1E52B98B62','5CA553826561D','EFDA3A4948D9E','FDB1C17AAF43A');
    	$permisos = Permiso::whereIn('clave',$claves_permisos)->lists('id');
        
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
