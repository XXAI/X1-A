<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Rol as Rol;
use App\Permiso as Permiso;

use Input, Response,  Validator, DB;
use Illuminate\Http\Response as HttpResponse;


class RolController extends Controller {

	/**
     * Instantiate a new UserController instance.
     */
    public function __construct()
    {
        $this->middleware('permisos:GET.5CA553826561D|POST.FDB1C17AAF43A|PUT.FDB1C17AAF43A|DELETE.FDB1C17AAF43A');
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(){
		try{
            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
        
            if($query){
            	$roles = Rol::where('nombre','LIKE','%'.$query.'%');
            } else {
                $roles = Rol::getModel();
            }

            $totales = $roles->count();
            $roles = $roles->skip(($pagina-1)*$elementos_por_pagina)
                        ->take($elementos_por_pagina)->get();

            /*if($roles){
				$roles->load('permisos');
			}*/

            return Response::json(['data'=>$roles,'totales'=>$totales],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$mensajes = [
			'required' 		=> "required",
			'email' 		=> "email",
			'accepted' 		=> "accepted",
			'confirmed' 	=> "confirmed",
			'unique' 		=> "unique",
			'url' 			=> "url",
			'date' 			=> "date"
		];

		$reglas = [
			'nombre'	=> 'required',
			'permisos'	=> 'array'			
		];
			
		
		$inputs = Input::all();		
		
		$v = Validator::make($inputs, $reglas, $mensajes);
		
	    if ($v->fails()) {
			return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
	    }
		try {
			
			$rol = Rol::create($inputs);
			
			$permisos = array();
			
			if(isset($inputs['permisos'])){
				foreach($inputs['permisos'] as $permiso){
					$permisos[] = $permiso['id'];
				}
			}
						
			if(count($permisos)>0)
				$rol->permisos()->sync($permisos);
			else
				$rol->permisos()->sync([]);
				
			
			return Response::json([ 'data' => $rol ],200);		
						
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id){
		try{
           	$rol = Rol::find($id);
           	
			if(!$rol){
				throw new Exception('No existe el rol');
			}
			
			$rol->permisos;
        	return Response::json(['data'=>$rol],200);
       	}catch(Exception $e){
             return Response::json(['error'=>$e->getMessage()],500);
    	}
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id){
		//
		$mensajes = [
			'required' 		=> "required",
			'email' 		=> "email",
			'accepted' 		=> "accepted",
			'confirmed' 	=> "confirmed",
			'unique' 		=> "unique",
			'url' 			=> "url",
			'date' 			=> "date"
		];
		$reglas = [
			'nombre'	=> 'required',
			'permisos' 	=> 'array',					
		];

		$inputs = Input::all();
		
		$v = Validator::make($inputs, $reglas, $mensajes);
	
	    if ($v->fails()) {
			return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
	    }
		try {
			
			$rol = Rol::find($id);
			
			if(!$rol){
				throw new Exception('No existe el rol');
			}
			
			$rol->nombre = $inputs['nombre'];
			$rol->save();					
			
			
			$permisos = array();
			if(isset($inputs['permisos'])){
				foreach($inputs['permisos'] as $permiso){
					$permisos[] = $permiso['id'];
				}
			}
						
			if(count($permisos)>0)
				$rol->permisos()->sync($permisos);
			else
				$rol->permisos()->sync([]);
			
			
			return Response::json(['data'=>$rol],200);		
						
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		try {
			Rol::destroy($id);
			return Response::json(['data'=>'Elemento eliminado con exito'],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
	}
}