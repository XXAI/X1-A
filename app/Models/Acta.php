<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Acta extends Model {
	protected $fillable = ['folio','clues','ciudad','fecha','hora_inicio','hora_termino','lugar_reunion','lugar_entrega','empresa_clave','estatus','director_unidad','administrador','encargado_almacen','coordinador_comision_abasto','numero','num_oficio','fecha_solicitud'];

	public function requisiciones(){
        return $this->hasMany('App\Models\Requisicion','acta_id');
    }

    public function unidadMedica(){
    	return $this->hasOne('App\Models\UnidadMedica','clues','clues');
    }
}