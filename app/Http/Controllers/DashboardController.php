<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Usuario as Usuario;
use App\Models\Acta as Acta;
use App\Models\ConfiguracionUnidades;
use Input, Response,  Validator;
use Illuminate\Http\Response as HttpResponse;


class DashboardController extends Controller {

	protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request){
		$user_email = $request->header('X-Usuario');

        $habilitar_captura = ConfiguracionUnidades::obtenerValor('habilitar_captura');
        $habilitar_captura_exfarma = ConfiguracionUnidades::obtenerValor('habilitar_captura_exfarma');

		$actas_sin_validar = Acta::where('estatus',2)->count();

		$actas = Acta::with('UnidadMedica')
                        ->take(10)
                        ->where('estatus',2)
                        ->orderBy('fecha_importacion','asc')
                        ->get();
        $datos = [
        	'actas' => $actas,
        	'actas_sin_validar'=> $actas_sin_validar,
            'actas_activas_otros' => $habilitar_captura->valor,
            'actas_activas_exfarma' => $habilitar_captura_exfarma->valor,
        ];
        return Response::json(['data'=>$datos],200);
	}
}