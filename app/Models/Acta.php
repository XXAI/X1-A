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

    public function proveedores(){
    	return $this->belongsToMany('\App\Models\Proveedor', 'acta_proveedor', 'acta_id', 'proveedor_id')
    				->withPivot('num_oficio');
    }
	
	public function empresa(){
    	return $this->hasOne('App\Models\Empresa','clave','empresa_clave');
    }

    public function entradas(){
        return $this->hasMany('App\Models\Entrada','acta_id')->orderBy('fecha_recibe','desc')->orderBy('hora_recibe','desc');
    }
	
}