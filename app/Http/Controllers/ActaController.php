<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive;

class ActaController extends Controller
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

            $totales = $recurso->count();
            
            $recurso = $recurso->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->orderBy('id','desc')->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'folio'             =>'required|unique:actas',
            'empresa'           =>'required',
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'acta_id'           =>'required',
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required',
            'firma_solicita'    =>'required',
            'firma_director'    =>'required'
        ];

        //$inputs = Input::all();

        try {
            if(Input::hasFile('zipfile')){

                $destinationPath = storage_path().'/app/imports/';
                $upload_success = Input::file('zipfile')->move($destinationPath, "archivo_zip.zip");
                exec('unzip -P S3CR3T0 '.$destinationPath.'archivo_zip.zip -d '.$destinationPath);

                $str = file_get_contents($destinationPath.'acta.json');
                $json = json_decode($str, true);

                $v = Validator::make($json, $reglas_acta, $mensajes);
                if ($v->fails()) {
                    Storage::deleteDirectory("imports");
                    return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                }

                $json['empresa_clave'] = $json['empresa'];
                $folio_array = explode('/', $json['folio']);
                $json['clues'] = $folio_array[0];

                DB::beginTransaction();

                $acta = Acta::create($json);

                foreach ($json['requisiciones'] as $inputs_requisicion) {
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                    }

                    $inputs_requisicion['empresa_clave'] = $inputs_requisicion['empresa'];
                    $requisicion = $acta->requisiciones()->create($inputs_requisicion);

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['id'],
                                'cantidad' => $req_insumo['pivot']['cantidad'],
                                'total' => $req_insumo['pivot']['total'],
                                'cantidad_aprovada' => $req_insumo['pivot']['cantidad'],
                                'total_aprovado' => $req_insumo['pivot']['total']
                            ];
                        }
                        $requisicion->insumos()->sync($insumos);
                    }
                }

                DB::commit();

                Storage::deleteDirectory("imports");

                return Response::json([ 'data' => $json ],200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory("imports");
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
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

    public function generarActaPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::with('requisiciones')->find($id);

        if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }

        $pedidos = $data['acta']->requisiciones->lists('pedido')->toArray();
        $data['acta']->requisiciones = implode(', ', $pedidos);

        $data['acta']->hora_inicio = substr($data['acta']->hora_inicio, 0,5);
        $data['acta']->hora_termino = substr($data['acta']->hora_termino, 0,5);

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        $data['unidad'] = env('CLUES_DESCRIPCION');
        $data['empresa'] = env('EMPRESA');
        
        $pdf = PDF::loadView('pdf.acta', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-100), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream($data['acta']->folio.'-Acta.pdf');
    }

    public function generarRequisicionPDF($id){
        $data = [];
        $data['acta'] = Acta::with('requisiciones.insumos')->find($id);

        if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }

        $data['unidad'] = env('CLUES_DESCRIPCION');
        $data['empresa'] = env('EMPRESA');

        $pdf = PDF::loadView('pdf.requisiciones', $data);
        return $pdf->stream($data['acta']->folio.'Requisiciones.pdf');
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

        $reglas_acta = [
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            'empresa'           =>'required',
            'firma_solicita'    =>'required',
            'firma_director'    =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'sometimes_required',
            'firma_solicita'    =>'required',
            'firma_director'    =>'required'
        ];

        $inputs = Input::all();
        //$inputs = Input::only('id','servidor_id','password','nombre', 'apellidos');
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_acta, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {

            DB::beginTransaction();

            $acta = Acta::find($id);

            if($acta->estatus == 2){
                throw new \Exception("El Acta no se puede editar ya que se encuentra con estatus de finalizada");
            }

            $acta->update($inputs);

            if(isset($inputs['requisiciones'])){
                if(count($inputs['requisiciones']) > 3){
                    throw new \Exception("No pueden haber mas de tres requesiciones por acta");
                }

                $acta->load('requisiciones');
                $requisiciones_guardadas = [];
                foreach ($inputs['requisiciones'] as $inputs_requisicion) {
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                    }

                    if(isset($inputs_requisicion['id'])){
                        $requisicion = Requisicion::find($inputs_requisicion['id']);
                        $requisicion->update($inputs_requisicion);
                        $requisiciones_guardadas[$requisicion->id] = true;
                    }else{
                        $max_requisicion = Requisicion::where('tipo_requisicion',$inputs_requisicion['tipo_requisicion'])->max('numero');
                        if(!$max_requisicion){
                            $max_requisicion = 0;
                        }
                        $inputs_requisicion['numero'] = $max_requisicion+1;
                        $inputs_requisicion['acta_id'] = $acta->id;
                        $inputs_requisicion['empresa'] = env('EMPRESA');
                        $requisicion = Requisicion::create($inputs_requisicion);
                    }

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total']
                            ];
                        }
                        $requisicion->insumos()->sync($insumos);
                    }else{
                        $requisicion->insumos()->sync([]);
                    }
                }
                $eliminar_requisiciones = [];
                foreach ($acta->requisiciones as $requisicion) {
                    if(!isset($requisiciones_guardadas[$requisicion->id])){
                        $eliminar_requisiciones[] = $requisicion->id;
                        $requisicion->insumos()->sync([]);
                    }
                }
                if(count($eliminar_requisiciones)){
                    Requisicion::whereIn('id',$eliminar_requisiciones)->delete();
                }
            }

            DB::commit();

            return Response::json([ 'data' => $acta ],200);

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
}
