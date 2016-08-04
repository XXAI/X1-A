<?php namespace App\Http\Middleware;

use Closure;
use Request;
use Response;
use Exception;
use App;


class OAuth {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		try{
			
			$access_token = str_replace('Bearer ','',Request::header('Authorization'));	
		
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, env('OAUTH_SERVER').'/oauth/check/'.$access_token.'/'.Request::header('X-Usuario'));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			
	        // Execute & get variables
	        $api_response = json_decode(curl_exec($ch)); 
	        $curlError = curl_error($ch);
	        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        
	        if($curlError){ 
	        	 throw new Exception("Hubo un problema al validar el token de acceso. cURL problem: $curlError"); 
	         
	        // Tet if there is a 4XX error (request went through but erred). 
	        }
	        
	        if($http_code != 200){
				if(isset($api_response->error)){
					return Response::json(['error'=>$api_response->error],$http_code);	
				}else{
					return Response::json(['error'=>"Error"],$http_code);
				}
	        }   
	        // Si llegamos a este punto el token es valido
			return $next($request);
	        
	    }catch(Exception $e){
			return Response::json(['error'=>$e->getMessage()],500);
	    }
		
		
	}

}
