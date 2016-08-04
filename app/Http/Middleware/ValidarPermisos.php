<?php namespace App\Http\Middleware;

use Closure;
use Request;
use Response;
use Exception;
use App;


class ValidarPermisos {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next,$permisos)
	{
		try{

			$http_code = 200;
			$array_permisos = explode('|', $permisos);
			$metodo = $request->method();

			$permisos = array();
			foreach ($array_permisos as $permiso) {
				$partes_permiso = explode('.', $permiso);
				if($metodo == $partes_permiso[0]){
					$permisos[] = $partes_permiso[1];
				}
			}

			$user_email = Request::header('X-Usuario');

			$acceso = App\Usuario::obtenerClavesPermisos()
									->where('usuarios.email','=',$user_email)
									->whereIn('permisos.clave',$permisos)
									->get();
			
			if(count($acceso) == 0){
				$http_code = 403;
			}

	        if($http_code != 200){
	        	return Response::json(['error'=>"Error"],$http_code);
	        }   
	        // Si llegamos a este punto el token es valido
			return $next($request);
	        
	    }catch(Exception $e){
			return Response::json(['error'=>$e->getMessage()],500);
	    }
		
		
	}

}
