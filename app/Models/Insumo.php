<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Insumo extends Model {
	public function scopePorEmpresa($query,$empresa_clave){
		return $query->select('id','pedido_'.$empresa_clave.' AS pedido','requisicion','lote','clave','descripcion',
				'marca_'.$empresa_clave.' AS marca','unidad','precio_'.$empresa_clave.' AS precio','tipo','cause');
	}
}