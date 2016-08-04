<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model {

	public function roles(){
		return $this->belongsToMany('App\Rol','permiso_rol', 'permiso_id', 'rol_id');
	}
}
