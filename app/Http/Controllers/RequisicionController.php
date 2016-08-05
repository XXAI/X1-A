<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive;

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

            $recurso = Requisicion::leftjoin('actas','actas.id','=','requisiciones.acta_id')
                                    ->where('requisiciones.estatus','>=',1);

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

    public function generarRequisicionPDF($id){
        $data = [];
        $data['requisicion'] = Requisicion::with('acta','insumos')->find($id);

        if(!$data['requisicion']->estatus){
            return Response::json(['error' => 'No se puede generar el archivo por que la requisición no se encuentra aprobada'], HttpResponse::HTTP_CONFLICT);
        }

        $data['unidad'] = $data['requisicion']->acta->clues;
        $data['empresa'] = $data['requisicion']->acta->empresa_clave;

        $pdf = PDF::loadView('pdf.requisicion', $data);
        return $pdf->stream('Requisicion-'.$data['requisicion']->acta->folio.'-'.$data['requisicion']->tipo_requisicion.'-'.$data['requisicion']->numero.'.pdf');
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

            if($requisicion->estatus == 2){
                throw new \Exception("La Requisición no se puede editar ya que se encuentra con estatus de enviada");
            }

            $requisicion->estatus = Input::get('estatus');
            
            if($requisicion->estatus == 1){
                $requisicion->sub_total = Input::get('sub_total');
                $requisicion->gran_total = Input::get('gran_total');
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
