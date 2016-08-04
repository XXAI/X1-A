<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Permiso as Permiso;

use Input, Response,  Validator;
use Illuminate\Http\Response as HttpResponse;


class PermisoController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$query = Input::get('query');
		
		if($query){
			$permisos = Permiso::where(function($query_where)use($query){
				$query_where->where('clave','LIKE','%'.$query.'%')
							->orWhere('grupo','LIKE','%'.$query.'%')
							->orWhere('descripcion','LIKE','%'.$query.'%');
			})->get();
		} else {
			$permisos = Permiso::all();
		}
		
		return Response::json(['data'=>$permisos],200);
	}
}