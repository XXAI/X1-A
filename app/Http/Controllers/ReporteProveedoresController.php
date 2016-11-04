<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\UnidadMedica;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Font_Metrics, \PDF, \Storage, \ZipArchive, DateTime;
use \Excel;

class ReporteProveedoresController extends Controller{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{
            //DB::enableQueryLog();
            
            $recurso = DB::table('requisicion_insumo')
                            ->select('proveedores.id','proveedores.nombre', 
                                //DB::raw('count(distinct requisicion_insumo.requisicion_id) as total_requisiciones'),
                                DB::raw('count(distinct requisicion_insumo.insumo_id) as total_claves'),
                                //DB::raw('sum(if(requisicion_insumo.cantidad_recibida > 0,1,0)) as total_claves_recibidas'),
                                DB::raw('count(distinct  case when requisicion_insumo.cantidad_recibida > 0 then requisicion_insumo.insumo_id end) as total_claves_recibidas'),
                                DB::raw('sum(requisicion_insumo.cantidad_validada) as total_lotes'), 
                                DB::raw('sum(requisicion_insumo.cantidad_recibida) as total_lotes_recibidos')
                            )->leftJoin('proveedores','proveedores.id','=','requisicion_insumo.proveedor_id')
                            ->whereNotNull('requisicion_insumo.proveedor_id')
                            ->groupBy('requisicion_insumo.proveedor_id')->get();
            /*
            if($query){
                $recurso = $recurso->where(function($condition)use($query){
                    $condition->where('folio','LIKE','%'.$query.'%')
                            ->orWhere('clues','LIKE','%'.$query.'%')
                            ->orWhere('lugar_reunion','LIKE','%'.$query.'%')
                            ->orWhere('ciudad','LIKE','%'.$query.'%');
                });
            }

            if($filtro){
                if(isset($filtro['estatus'])){
                    if($filtro['estatus'] == 'pedidos'){
                        $recurso = $recurso->whereNotNull('fecha_termino');
                    }else if($filtro['estatus'] == 'pendientes'){
                        $recurso = $recurso->whereNull('fecha_termino');
                    }
                }
            }
            */

            //$recurso = $recurso->skip(($pagina-1)*$elementos_por_pagina)
                                //->take($elementos_por_pagina)
                                //->orderBy('requisicion_insumo.proveedor_id','asc')
                                //->get();

            //$totales = count($recurso);

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id){
        $proveedor = Proveedor::find($id);
        $clues = DB::table('requisicion_insumo AS RI')
                            ->select('A.clues', 'C.nombre',
                                DB::raw('sum(RI.cantidad_validada) as total_lotes'),  
                                DB::raw('sum(RI.cantidad_recibida) as total_lotes_recibidos'),
                                DB::raw('count(distinct RI.insumo_id) as total_claves'),
                                DB::raw('sum(if(RI.cantidad_recibida > 0,1,0)) as total_claves_recibidas')
                            )
                            ->leftJoin('requisiciones AS R','R.id','=','RI.requisicion_id')
                            ->leftJoin('actas AS A','A.id','=','R.acta_id')
                            ->leftJoin('clues AS C','C.clues','=','A.clues')
                            ->where('RI.proveedor_id',$id)
                            ->groupBy('A.clues')->get();

        return Response::json([ 'data' => ['proveedor'=>$proveedor, 'clues'=>$clues]],200);
    }
}

?>