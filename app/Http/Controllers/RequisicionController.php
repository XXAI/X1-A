<?php

namespace App\Http\Controllers;

use App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;


use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Usuario;
use App\Models\UnidadMedica;
use App\Models\Configuracion;
use App\Models\TipoClues;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Font_Metrics, \PDF, \Storage, DateTime;

class RequisicionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){

        try{
            $user_email = $request->header('X-Usuario');
            $usuario = Usuario::where('email',$user_email)->first();
            $seleccion_default = $usuario->tipos_clues;
            if($seleccion_default){
                $tipos_clues = explode(',',$seleccion_default);
            }else{
                $tipos_clues = [];
            }
            

            //DB::enableQueryLog();
            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
            $filtro = Input::get('filtro');

            $recurso = Acta::getModel();
            
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
                    if($filtro['estatus'] == 'validados'){
                        $recurso = $recurso->whereNotNull('fecha_validacion');
                    }else if($filtro['estatus'] == 'pendientes'){
                        $recurso = $recurso->whereNull('fecha_validacion');
                    }
                }
                if(isset($filtro['tipo'])){
                    $tipos_clues = explode(',',$filtro['tipo']);
                }
            }

            if(count($tipos_clues)){
                $clues = UnidadMedica::whereIn('tipo_clues',$tipos_clues)->get()->lists('clues');
                $recurso = $recurso->whereIn('clues',$clues);
            }
            
            $totales = $recurso->count();
            
            $recurso = $recurso->with('UnidadMedica','requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)
                                ->take($elementos_por_pagina)
                                ->orderBy('estatus','asc')
                                ->orderBy('estatus_sincronizacion','asc')
                                ->orderBy('fecha_importacion','desc')
                                ->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
    }

    public function catalogos(Request $request){
        try{
            $user_email = $request->header('X-Usuario');
            $usuario = Usuario::where('email',$user_email)->first();
            $seleccion_default = $usuario->tipos_clues;

            $tipos_clues = TipoClues::get();
            return Response::json(['data'=>['tipos_clues'=>$tipos_clues,'tipos_clues_default'=>$seleccion_default]],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$ex->getMessage()],500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){

        $acta = Acta::with([
			'requisiciones'=>function($query){
				$query->orderBy('tipo_requisicion');
			},'requisiciones.insumos'=>function($query){
            	$query->orderBy('lote');
	        },'requisiciones.insumosClues','unidadMedica','empresa'])->find($id);

        $configuracion = Configuracion::find(1);
        $max_oficio = Acta::max('num_oficio');
        if(!$max_oficio){
            $max_oficio = 0;
        }

        $clues = [];
        foreach ($acta->requisiciones as $requisicion) {
            $clues = array_merge($clues,$requisicion->insumosClues->lists('pivot.clues')->toArray());
        }
        $clues = UnidadMedica::whereIn('clues',$clues)->lists('nombre','clues');

        return Response::json([ 'data' => $acta, 'configuracion'=>$configuracion, 'clues' => $clues,'oficio'=> $max_oficio+1 ],200);
    }

    public function generarSolicitudesPDF($id){
        $data = [];
        $acta = Acta::with(['requisiciones'=>function($query){
            $query->where('gran_total_validado','>',0);
        }])->find($id);

        //$data['acta'] = $acta;
        $empresas = Empresa::where('clave','=',$acta->empresa_clave)->get();

        $data['empresa'] = [
            'nombre' => $empresas[0]->nombre,
            'clave' => $empresas[0]->clave,
            'partidas' => $empresas->lists('partida_presupuestal','pedido')
        ];

        $data['configuracion'] = Configuracion::find(1);

        $data['unidad'] = UnidadMedica::where('clues',$acta->clues)->first();

        $empresa_clave = $data['empresa']['clave'];
        $acta->requisiciones->load(['insumos'=>function($query){
            $query->wherePivot('cantidad_validada','>',0)
                ->orderBy('lote');
        }]);

        $acta = $acta->toArray();

        for($i = 0, $c = count($acta['requisiciones']); $i < $c; $i++){
            $requisicion = $acta['requisiciones'][$i];
            for($j = 0, $d = count($requisicion['insumos']); $j < $d; $j++){
                $req_insumo = $requisicion['insumos'][$j];
                $insumo = [
                    'lote'              => $req_insumo['lote'],
                    'clave'             => $req_insumo['clave'],
                    'descripcion'       => $req_insumo['descripcion'],
                    'cantidad_validada' => number_format($req_insumo['pivot']['cantidad_validada']),
                    'unidad'            => $req_insumo['unidad'],
                    'precio'            => '$ ' . number_format($req_insumo['precio'],2),
                    'total_validado'    => '$ ' . number_format($req_insumo['pivot']['total_validado'],2)
                ];
                $requisicion['insumos'][$j] = $insumo;
            }
            $acta['requisiciones'][$i] = $requisicion;
        }

        $data['acta'] = $acta;
        //var_dump($acta);die;
        /*
        $acta->requisiciones->load(['insumos'=>function($query)use($empresa_clave){
            $query->select('id','pedido','requisicion','lote','clave','descripcion' ,'marca','unidad','precio',
                            'tipo','cause')->where('proveedor',$empresa_clave);
        }]);
        */

        $pdf = PDF::loadView('pdf.solicitudes', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-40), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream('Solicitudes-'.$acta['folio'].'.pdf');
    }

    public function generarOficioPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::with(['requisiciones'=>function($query){
            $query->where('gran_total_validado','>',0);
        }])->find($id);
        
        if($data['acta']->fecha_solicitud){
            $fecha = explode('-',$data['acta']->fecha_solicitud);
        }else{
            $fecha = date('YYYY-m-d');
            $fecha = explode('-',$fecha);
        }

        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha_solicitud = $fecha;
        

        $data['empresa'] = Empresa::where('clave','=',$data['acta']->empresa_clave)->first();
        $data['configuracion'] = Configuracion::find(1);
        $data['unidad'] = UnidadMedica::where('clues',$data['acta']->clues)->first();

        $pedidos = array_keys($data['acta']->requisiciones->lists('pedido','pedido')->toArray());
        $numeros = $data['acta']->requisiciones->lists('numero')->toArray();
        if(count($pedidos) == 1){
            $data['acta']->requisiciones = $pedidos[0];
        }elseif(count($pedidos) == 2){
            $data['acta']->requisiciones = $pedidos[0] . ' y ' . $pedidos[1];
        }else{
            $data['acta']->requisiciones = $pedidos[0] . ', ' . $pedidos[1] . ' y ' . $pedidos[2];
        }

        $data['acta']->numeros = '';
        if(count($numeros) > 2){
            $separador = '';
            for ($i=0; $i < count($numeros)-1 ; $i++) { 
                $data['acta']->numeros .= $separador . $numeros[$i];
                $separador = ', ';
            }
            $data['acta']->numeros .= ' y ' . $numeros[count($numeros)-1];
        }elseif(count($numeros) > 1){
            $data['acta']->numeros = $numeros[0] . ' y ' . $numeros[1];
        }else{
            $data['acta']->numeros = $numeros[0];
        }
        
        $pdf = PDF::loadView('pdf.oficio', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-100), "{PAGE_NUM}/{PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream($data['acta']->folio.'-Acta.pdf');
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
            'unique'        => "unique",
            'date'          => "date"
        ];
        try {

            DB::beginTransaction();

            $requisicion = Requisicion::with('acta')->find($id);

            if($requisicion->estatus == 2){
                throw new \Exception("La Requisición no se puede editar ya que se encuentra con estatus de enviada");
            }

            if($requisicion->acta->estatus >= 3){
                throw new \Exception("La Requisición no se puede editar ya que el acta se encuentra con estatus de enviada");
            }

            $requisicion->estatus = Input::get('estatus');
            
            //if($requisicion->estatus == 1){
                //$requisicion->sub_total_validado = Input::get('sub_total');
                //$requisicion->iva_validado = Input::get('iva');
                //$requisicion->gran_total_validado = Input::get('gran_total');
            //}

            if($requisicion->save()){
                if($requisicion->estatus == 1){
                    if($requisicion->acta->empresa_clave == 'exfarma'){
                        $proveedor_id = 7;
                    }else{
                        $proveedor_id = null;
                    }

                    $inputs_insumos = Input::get('insumos');
                    $insumos = [];
                    foreach ($inputs_insumos as $req_insumo) {
                        $insumos[] = [
                            'insumo_id' => $req_insumo['insumo_id'],
                            'cantidad' => $req_insumo['cantidad'],
                            'total' => $req_insumo['total'],
                            'cantidad_validada' => $req_insumo['cantidad_validada'],
                            'total_validado' => $req_insumo['total_validado'],
                            'proveedor_id' => $proveedor_id
                        ];
                    }
                    $requisicion->insumos()->sync([]);
                    $requisicion->insumos()->sync($insumos);

                    $sub_total = $requisicion->insumos()->sum('total');
                    $requisicion->sub_total = $sub_total;
                    if($requisicion->tipo_requisicion == 3){
                        $requisicion->iva = $sub_total*16/100;
                    }else{
                        $requisicion->iva = 0;
                    }
                    $requisicion->gran_total = $sub_total + $requisicion->iva;

                    $sub_total = $requisicion->insumos()->sum('total_validado');
                    $requisicion->sub_total_validado = $sub_total;
                    if($requisicion->tipo_requisicion == 3){
                        $requisicion->iva_validado = $sub_total*16/100;
                    }else{
                        $requisicion->iva_validado = 0;
                    }
                    $requisicion->gran_total_validado = $sub_total + $requisicion->iva_validado;
                    
                    $requisicion->save();

                    //$requisicion->sub_total_validado = Input::get('sub_total');
                    //$requisicion->iva_validado = Input::get('iva');
                    //$requisicion->gran_total_validado = Input::get('gran_total');

                    if(Input::get('insumos_clues')){
                        $inputs_insumos = Input::get('insumos_clues');
                        $insumos = [];
                        foreach ($inputs_insumos as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total'],
                                'cantidad_validada' => $req_insumo['cantidad_validada'],
                                'total_validado' => $req_insumo['total_validado'],
                                'clues' => $req_insumo['clues'],
                                'requisicion_id_unidad' => $req_insumo['requisicion_id_unidad']
                            ];
                        }
                        $requisicion->insumosClues()->sync([]);
                        $requisicion->insumosClues()->sync($insumos);
                    }
                }
            }

            DB::commit();

            //return Response::json([ 'data' => $requisicion ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
