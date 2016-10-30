<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class ConfiguracionUnidades extends Model {
	//protected $connection = 'mysql_sync';
   	//protected $table = 'configuracion_aplicacion';
	protected $table = 'samm_unidades.configuracion_aplicacion';

	public function scopeObtenerValor($query,$variable){
		return $query->where('variable',$variable)->first();
	}
}