<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Confgiuracion extends Model {
	protected $table = 'configuracion';
	protected $fillable = ['nombre_solitante_requisicion','cargo_solitante_requisicion','director_atencion_medica','jefe_recursos_materiales','subdirector_recursos_materiales','director_administracion_finanzas'];
}