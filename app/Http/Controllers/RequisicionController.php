<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Models\UnidadMedica;
use App\Models\Configuracion;
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
            }

            $totales = $recurso->count();
            
            $recurso = $recurso->with('UnidadMedica','requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)
                                ->take($elementos_por_pagina)
                                ->orderBy('id','desc')->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
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
    public function show($id){
        return Response::json([ 'data' => Acta::with('requisiciones.insumos')->find($id) ],200);
    }

    public function generarSolicitudesPDF($id){
        $data = [];
        $acta = Acta::with('requisiciones')->find($id);

        $data['acta'] = $acta;
        $empresas = Empresa::where('clave','=',$acta->empresa_clave)->get();

        $data['empresa'] = [
            'nombre' => $empresas[0]->nombre,
            'clave' => $empresas[0]->clave,
            'partidas' => $empresas->lists('partida_presupuestal','pedido')
        ];

        $data['configuracion'] = Configuracion::find(1);

        $data['unidad'] = UnidadMedica::where('clues',$acta->clues)->first();

        $empresa_clave = $data['empresa']['clave'];
        $acta->requisiciones->load(['insumos'=>function($query)use($empresa_clave){
            $query->select('id','pedido','requisicion','lote','clave','descripcion' ,'marca','unidad','precio',
                            'tipo','cause')->where('proveedor',$empresa_clave);
        }]);

        /*if(!$acta->estatus){
            return Response::json(['error' => 'No se puede generar el archivo por que la requisición no se encuentra aprobada'], HttpResponse::HTTP_CONFLICT);
        }*/

        //$data['unidad'] = $data['requisicion']->acta->clues;
        //$data['empresa'] = $data['requisicion']->acta->empresa_clave;

        $pdf = PDF::loadView('pdf.solicitudes', $data);
        /*
        if($data['requisicion']->estatus == 1){
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $w = $canvas->get_width();
            $h = $canvas->get_height();
            $canvas->page_text(20, $h - 600, "SIN VALIDAR", Font_Metrics::get_font("arial", "bold"),85, array(0.85, 0.85, 0.85));
        }
        */
        return $pdf->stream('Solicitudes-'.$acta->folio.'.pdf');
    }

    public function generarOficioPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::with('requisiciones')->find($id);
        
        $fecha = explode('-',$data['acta']->fecha_solicitud);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha_solicitud = $fecha;

        $data['empresa'] = Empresa::where('clave','=',$data['acta']->empresa_clave)->first();
        $data['configuracion'] = Configuracion::find(1);
        $data['unidad'] = UnidadMedica::where('clues',$data['acta']->clues)->first();

        $pedidos = $data['acta']->requisiciones->lists('pedido')->toArray();
        $numeros = $data['acta']->requisiciones->lists('numero')->toArray();
        if(count($pedidos) == 1){
            $data['acta']->requisiciones = $pedidos[0];
            $data['acta']->numeros       = $numeros[0];
        }elseif(count($pedidos) == 2){
            $data['acta']->requisiciones = $pedidos[0] . ' y ' . $pedidos[1];
            $data['acta']->numeros       = $numeros[0] . ' y ' . $numeros[1];
        }else{
            $data['acta']->requisiciones = $pedidos[0] . ', ' . $pedidos[1] . ' y ' . $pedidos[2];
            $data['acta']->numeros       = $numeros[0] . ', ' . $numeros[1] . ' y ' . $numeros[2];
        }
        //$data['acta']->requisiciones = implode(', ', $pedidos);
        
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
            
            if($requisicion->estatus == 1){
                $requisicion->sub_total_validado = Input::get('sub_total');
                $requisicion->iva_validado = Input::get('iva');
                $requisicion->gran_total_validado = Input::get('gran_total');
            }

            if($requisicion->save()){
                if($requisicion->estatus == 1){
                    $inputs_insumos = Input::get('insumos');
                    $insumos = [];
                    foreach ($inputs_insumos as $req_insumo) {
                        $insumos[] = [
                            'insumo_id' => $req_insumo['insumo_id'],
                            'cantidad' => $req_insumo['cantidad'],
                            'total' => $req_insumo['total'],
                            'cantidad_aprovada' => $req_insumo['cantidad_aprovada'],
                            'total_aprovado' => $req_insumo['total_aprovado']
                        ];
                    }
                    $requisicion->insumos()->sync($insumos);
                }
            }

            DB::commit();

            return Response::json([ 'data' => $requisicion ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /*
    public function destroy($id){
        try {
            $acta = Acta::with('requisiciones')->find($id);
            foreach ($acta->requisiciones as $requisicion) {
                $requisicion->insumos()->sync([]);
            }
            Requisicion::where('acta_id',$id)->delete();
            Acta::destroy($id);
            return Response::json(['data'=>'Elemento eliminado con exito'],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
    */
}
