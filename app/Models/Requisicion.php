<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Requisicion extends Model {
	protected $table = 'requisiciones';
	protected $fillable = ['acta_id', 'numero', 'pedido', 'lotes', 'empresa_clave', 'tipo_requisicion', 'dias_surtimiento', 'sub_total', 'gran_total', 'iva', 'firma_solicita', 'firma_director'];

	public function detalles(){
        return $this->hasMany('App\Models\DetalleRequisicion','requisicion_id');
    }

    public function insumos(){
    	return $this->belongsToMany('\App\Models\Insumo', 'requisicion_insumo', 'requisicion_id', 'insumo_id')
    				->withPivot('cantidad','total','cantidad_aprovada','total_aprovado');
    }
}