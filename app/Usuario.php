<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model {

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable =  [ 'email'];

	public function scopeObtenerClavesPermisos($query){
		return $query->select('permisos.clave AS clavePermiso')
					->leftjoin('rol_usuario','usuario_id','=','usuarios.id')
					->leftjoin('permiso_rol','permiso_rol.rol_id','=','rol_usuario.rol_id')
					->leftjoin('permisos','permisos.id','=','permiso_rol.permiso_id')
					->groupBy('clavePermiso','usuarios.id');
	}
	//
	public function roles(){
		return $this->belongsToMany('App\Rol','rol_usuario', 'usuario_id', 'rol_id');
	}
}
