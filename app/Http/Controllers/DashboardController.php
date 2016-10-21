<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Usuario as Usuario;
use App\Models\Acta as Acta;

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

		$actas_sin_validar = Acta::where('estatus',2)->count();

		$actas = Acta::with('UnidadMedica')
                        ->take(10)
                        ->where('estatus',2)
                        ->orderBy('fecha_importacion','asc')
                        ->get();
        $datos = [
        	'actas' => $actas,
        	'actas_sin_validar'=> $actas_sin_validar
        ];
        return Response::json(['data'=>$datos],200);
	}
}