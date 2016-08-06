<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Font_Metrics, \PDF, \Storage, \ZipArchive;

class PedidoController extends Controller
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

            $recurso = Requisicion::leftjoin('actas','actas.id','=','requisiciones.acta_id')
                                    ->where('requisiciones.estatus','=',2);

            if($query){
                $recurso = $recurso->where(function($condition)use($query){
                    $condition->where('requisiciones.numero','LIKE','%'.$query.'%')
                            ->orWhere('requisiciones.pedido','LIKE','%'.$query.'%')
                            ->orWhere('requisiciones.empresa_clave','LIKE','%'.$query.'%')
                            ->orWhere('actas.clues','LIKE','%'.$query.'%')
                            ->orWhere('actas.folio','LIKE','%'.$query.'%');
                });
            }

            $totales = $recurso->count();
            
            $recurso = $recurso->select('actas.folio','actas.clues','requisiciones.*')
                                ->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
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
        return Response::json([ 'data' => Requisicion::with('acta','insumos')->find($id) ],200);
    }

    public function generarPedidoPDF($id){
        $data = [];
        $data['requisicion'] = Requisicion::with('acta','insumos')->find($id);

        if(!$data['requisicion']->estatus){
            return Response::json(['error' => 'No se puede generar el archivo por que la requisición no se encuentra aprobada'], HttpResponse::HTTP_CONFLICT);
        }

        $data['unidad'] = $data['requisicion']->acta->clues;
        $data['empresa'] = $data['requisicion']->acta->empresa_clave;

        $pdf = PDF::loadView('pdf.pedido', $data);

        /*
        if($data['requisicion']->estatus == 1){
            $pdf->output();
            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $w = $canvas->get_width();
            $h = $canvas->get_height();
            $canvas->page_text(20, $h - 300, "SIN VALIDAR", Font_Metrics::get_font("arial", "bold"),85, array(0.85, 0.85, 0.85));
        }
        */

        return $pdf->stream('Pedido-'.$data['requisicion']->acta->folio.'-'.$data['requisicion']->tipo_requisicion.'-'.$data['requisicion']->numero.'.pdf');
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

            $requisicion = Requisicion::find($id);

            if($requisicion->estatus < 2){
                throw new \Exception("La Requisición no se puede editar ya que no se encuentra con estatus de enviada");
            }

            $requisicion->estatus = Input::get('estatus');

            DB::commit();

            return Response::json([ 'data' => $requisicion ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
