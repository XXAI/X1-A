<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Insumo extends Model {
	public function scopePorEmpresa($query,$empresa_clave){
		return $query->select('id','pedido','requisicion','lote','clave','descripcion',
				'marca','unidad','precio','tipo','cause')->where('proveedor',$empresa_clave);
	}
}