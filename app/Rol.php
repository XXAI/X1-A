<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'roles';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable =  [ 'nombre' ];
	
	public function permisos(){
		return $this->belongsToMany('App\Permiso','permiso_rol', 'rol_id', 'permiso_id');
	}

	public function usuarios(){
		return $this->belongsToMany('App\Usuario','rol_usuario', 'rol_id', 'usuario_id');
	}
}
