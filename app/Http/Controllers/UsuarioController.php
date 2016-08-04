<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Usuario as Usuario;

use Input, Response,  Validator;
use Illuminate\Http\Response as HttpResponse;


class UsuarioController extends Controller {

	/**
     * Instantiate a new UserController instance.
     */
    public function __construct()
    {
        $this->middleware('permisos:GET.56C1E52B98B62|POST.EFDA3A4948D9E|PUT.EFDA3A4948D9E|DELETE.EFDA3A4948D9E');
    }

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		try{
            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
        
            if($query){
            	$usuarios = Usuario::where('email','LIKE','%'.$query.'%');
            } else {
                $usuarios = Usuario::getModel();
            }

            $totales = $usuarios->count();
            $usuarios = $usuarios->skip(($pagina-1)*$elementos_por_pagina)
                        ->take($elementos_por_pagina)->get();

            return Response::json(['data'=>$usuarios,'totales'=>$totales],200);
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
			'email'	=> 'required|email',
			'roles'	=> 'array'			
		];
			
		
		$inputs = Input::all();		
		
		$v = Validator::make($inputs, $reglas, $mensajes);
		
	    if ($v->fails()) {
			return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
	    }
		try {
			
			$usuario = Usuario::create($inputs);
			
			$roles = array();
			
			if(isset($inputs['roles'])){
				foreach($inputs['roles'] as $rol){
					$roles[] = $rol['id'];
				}
			}
						
			if(count($roles)>0)
				$usuario->roles()->sync($roles);
			else
				$usuario->roles()->sync([]);
				
			
			return Response::json([ 'data' => $usuario ],200);		
						
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
			$usuario = Usuario::find($id);
			if(!$usuario){
				throw new Exception('No existe el usuario');
			}
			$usuario->load('roles.permisos');
        	return Response::json(['data'=>$usuario],200);
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
			'email'		=> 'required|email',
			'roles' 	=> 'array',					
		];

		$inputs = Input::all();
		
		$v = Validator::make($inputs, $reglas, $mensajes);
	
	    if ($v->fails()) {
			return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
	    }
		try {
			
			$usuario = Usuario::find($id);
			
			if(!$usuario){
				throw new Exception('No existe el usuario');
			}
			
			$usuario->email = $inputs['email'];
			$usuario->save();					
			
			
			$roles = array();
			if(isset($inputs['roles'])){
				foreach($inputs['roles'] as $rol){
					$roles[] = $rol['id'];
				}
			}
						
			if(count($roles)>0)
				$usuario->roles()->sync($roles);
			else
				$usuario->roles()->sync([]);
			
			
			return Response::json(['data'=>$usuario],200);		
						
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
			Usuario::destroy($id);
			return Response::json(['data'=>'Elemento eliminado con exito'],200);
		} catch (Exception $e) {
		   return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
		}
	}
}