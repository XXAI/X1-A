<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Models\UnidadMedica;
use App\Models\ConfiguracionUnidades;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, DateTime, Exception;

class ConfiguracionUnidadesController extends Controller
{
    public function habilitarCaptura($estatus){
        try{
        	
            $parametros = Input::all();

            if($parametros['tipo'] == 'exfarma'){
                $habilitar_captura = ConfiguracionUnidades::obtenerValor('habilitar_captura_exfarma');
            }else{
                $habilitar_captura = ConfiguracionUnidades::obtenerValor('habilitar_captura');
            }
        	
        	if($estatus){
        		$habilitar_captura->valor = 1;
        		$mensaje = 'Habilitado';
        	}else{
        		$habilitar_captura->valor = 0;
        		$mensaje = 'Deshabilidato';
        	}

        	$habilitar_captura->save();

        	return Response::json([ 'data' => $mensaje], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}