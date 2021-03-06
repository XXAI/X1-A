<?php
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Input;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'WelcomeController@index');

Route::get('home', 'HomeController@index');

Route::get('create-hash',function(){
    try{
        $word = Input::get('word');
        $passwords = [];
        if($word){
            $passwords[$word]= Hash::make($word);
        }else{
            $words = Input::get('words');
            if($words){
                foreach ($words as $word) {
                    $passwords[$word]= Hash::make($word);
                }
            }else{
                return Response::json(['message'=>'Sin parametros validos'],200);
            }
        }
        return Response::json(['passwords'=>$passwords],200);
        
    }catch(Exception $e){
        return Response::json(['message'=>$e->getMessage(),'line'=>$e->getLine()],500);
    }
});

Route::controllers([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);


Route::post('/refresh-token', function(){
    try{
        
        $refresh_token =  Crypt::decrypt(Input::get('refresh_token'));
        $access_token = str_replace('Bearer ','',Request::header('Authorization'));	
        $post_request = 'grant_type=refresh_token'
                    .'&client_id='.env('CLIENT_ID')
                    .'&client_secret='.env('CLIENT_SECRET')
                    .'&refresh_token='.$refresh_token
                    .'&access_token='.$access_token; 
                 
                    
        $ch = curl_init();
        $header[]         = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $header);
        curl_setopt($ch, CURLOPT_URL, env('OAUTH_SERVER').'/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_request);
         
        // Execute & get variables
        $api_response = json_decode(curl_exec($ch)); 
        $curlError = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($curlError){ 
        	 throw new Exception("Hubo un problema al intentar hacer la autenticacion. cURL problem: $curlError");
        }
        
        if($http_code != 200){
            return Response::json(['error'=>$api_response->error],$http_code);
        }        
        
        //Encriptamos el refresh token para que no quede 100% expuesto en la aplicacion web
        $refresh_token_encrypted = Crypt::encrypt($api_response->refresh_token);
                    
        return Response::json(['access_token'=>$api_response->access_token,'refresh_token'=>$refresh_token_encrypted],200);
    }catch(Exception $e){
         return Response::json(['error'=>$e->getMessage()],500);
    }
});

Route::post('/signin', function () {
    try{
        $credentials = Input::only('email', 'password');
    
        $post_request = 'grant_type=password'
                    .'&client_id='.env('CLIENT_ID')
                    .'&client_secret='.env('CLIENT_SECRET')
                    .'&username='.$credentials['email']
                    .'&password='.$credentials['password']; 
                 
           
        $ch = curl_init();
        $header[]         = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $header);
        curl_setopt($ch, CURLOPT_URL, env('OAUTH_SERVER').'/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_request);
      
        // Execute & get variables
        $api_response = json_decode(curl_exec($ch)); 
        $curlError = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($curlError){ 
        	 throw new Exception("Hubo un problema al intentar hacer la autenticacion. cURL problem: $curlError");
        }
        
        if($http_code != 200){
          if(isset($api_response->error)){
				return Response::json(['error'=>$api_response->error],$http_code);	
			}else{
				return Response::json(['error'=>"Error"],$http_code);
			}
        }        
        //Encriptamos el refresh token para que no quede 100% expuesto en la aplicacion web
        $refresh_token_encrypted = Crypt::encrypt($api_response->refresh_token);
        
        return Response::json(['access_token'=>$api_response->access_token,'refresh_token'=>$refresh_token_encrypted],200);
    }catch(Exception $e){
         return Response::json(['error'=>$e->getMessage()],500);
    }
    
});

Route::group([ 'prefix' => 'v1'], function () {
    Route::get('solicitudes-pdf/{id}',    'RequisicionController@generarSolicitudesPDF');
    Route::get('oficio-pdf/{id}',    'RequisicionController@generarOficioPDF');
    //Route::get('pedidos-pdf/{id}',    'PedidoController@generarPedidoPDF');
    Route::get('notificacion-pdf/{id}',    'PedidoController@generarNotificacionPDF');
    Route::get('exportar-csv/{id}',         'ActaController@generarJSON');
    Route::get('pedidos-excel/{id}','PedidosExcelController@generar');
    Route::get('entrada-acta-excel/{id}',   'RecepcionController@generarExcel');
    Route::get('entrada-acta-excel-concentrado',   'RecepcionController@generarExcelConcentrado');

    Route::group(['middleware' => 'oauth'], function(){
          Route::get('/permisos-autorizados', function () {     
                //return Response::json(['error'=>"ERROR_PERMISOS"],500);
                $user_email = Request::header('X-Usuario');
                $permisos = App\Usuario::obtenerClavesPermisos()->where('usuarios.email','=',$user_email)->get()->lists('clavePermiso');
                return Response::json(['permisos'=>$permisos],200);
           });
           
           Route::post('/validacion-cuenta', function () {
               try{
                    
                    // En este punto deberíamos buscar en la base de datos la cuenta del usuario
                    // que previamente el adminsitrador debió haber regitrado, incluso aunque sea una cuenta
                    // OAuth valida.
                    $user_email = Request::header('X-Usuario');
                    $user = App\Usuario::where('email','=',$user_email)->first();

                    if(!$user){
                        return Response::json(['error'=>"CUENTA_VALIDA_NO_AUTORIZADA"],403);
                    }
                    
                    $access_token = str_replace('Bearer ','',Request::header('Authorization'));	
                    $post_request = 'access_token='.$access_token; 
                             
                                
                    $ch = curl_init();
                    $header[]         = 'Content-Type: application/x-www-form-urlencoded';
                    curl_setopt($ch, CURLOPT_HTTPHEADER,     $header);
                    curl_setopt($ch, CURLOPT_URL, env('OAUTH_SERVER').'/oauth/vinculacion');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_request);
                     
                    // Execute & get variables
                    $api_response = json_decode(curl_exec($ch)); 
                    $curlError = curl_error($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    if($curlError){ 
                    	 throw new Exception("Hubo un problema al intentar hacer la vinculación. cURL problem: $curlError");
                    }
                    
                    if($http_code != 200){
                        return Response::json(['error'=>$api_response->error],$http_code);
                    }
                             
                    return Response::json(['data'=>'Vinculación exitosa'],200);
                }catch(Exception $e){
                     return Response::json(['error'=>$e->getMessage()],500);
                }
           });
            
            Route::get('catalogos/usuarios', 'UsuarioController@catalogos');
            Route::get('catalogos/requisiciones', 'RequisicionController@catalogos');

            Route::resource('usuarios', 'UsuarioController',    ['only' => ['index', 'show','store', 'update', 'destroy']]);
            Route::resource('roles', 'RolController',           ['only' => ['index', 'show','store', 'update', 'destroy']]);
            Route::resource('permisos', 'PermisoController',    ['only' => ['index', 'show','store', 'update', 'destroy']]);

            Route::resource('dashboard', 'DashboardController', ['only' => ['index']]);

            Route::get('sincronizar-validacion/{id}','ActaController@sincronizar');
            Route::get('sincronizar-pedido/{id}', 'PedidoController@sincronizar');
            Route::get('generar-pedidos/{id}',    'PedidoController@generarPedidos');

            Route::resource('clues',        'CluesController',              ['only' => ['index']]);
            Route::put('clonar-acta/{id}',  'ClonarActasController@clonar');

            Route::get('habilitar-captura-acta/{estatus}','ConfiguracionUnidadesController@habilitarCaptura');

            Route::resource('actas',                'ActaController',               ['only' => ['index', 'show','store', 'update', 'destroy']]);
            Route::resource('requisiciones',        'RequisicionController',        ['only' => ['index', 'show', 'update']]);
            Route::resource('pedidos',              'PedidoController',             ['only' => ['index', 'show', 'update']]);
            Route::resource('recepcion',            'RecepcionController',          ['only' => ['index', 'show']]);
            Route::resource('reportes/proveedores', 'ReporteProveedoresController', ['only' => ['index', 'show']]);

            Route::get('ver-entrada/{id}',          'RecepcionController@showEntrada');
    });
   
   Route::get('/restricted', function () {
       return ['data' => 'This has come from a dedicated API subdomain with restricted access.'];
   });
});

