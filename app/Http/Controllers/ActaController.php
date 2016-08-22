<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, DateTime;

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
            
            $recurso = $recurso->with('requisiciones','unidadMedica')
                                ->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->orderBy('id','desc')->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$e->getMessage()],500);
        }
    }

    function decryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $crypttext = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $crypttext, MCRYPT_MODE_ECB, $iv);
       return trim($decrypttext);
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
            'folio'                         => 'required|unique:actas',
            'numero'                        => 'required',
            'empresa'                       => 'required',
            'ciudad'                        => 'required',
            'fecha'                         => 'required',
            'hora_inicio'                   => 'required',
            'hora_termino'                  => 'required',
            'lugar_reunion'                 => 'required',
            'requisiciones'                 => 'required|array|min:1'
            //'director_unidad'               => 'required',
            //'administrador'                 => 'required'
            //'encargado_almacen'             => 'required',
            //'coordinador_comision_abasto'   => 'required'
        ];

        $reglas_requisicion = [
            'acta_id'           =>'required',
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required'
        ];

        //$inputs = Input::all();

        try {
            if(Input::hasFile('zipfile')){
                $user_email = $request->header('X-Usuario');
                $user_email = str_replace('@','_',$user_email);
                $destinationPath = storage_path().'/app/imports/'.$user_email.'/';
                $upload_success = Input::file('zipfile')->move($destinationPath, 'archivo_zip.zip');

                $zip = new ZipArchive;
                $res = $zip->open($destinationPath.'archivo_zip.zip');
                if ($res === TRUE) {
                    $zip->extractTo($destinationPath);
                    $zip->close();
                } else {
                    return Response::json(['error' => 'No se pudo extraer el archivo'], HttpResponse::HTTP_CONFLICT);
                }
                
                $filename = $destinationPath . 'acta.json';
                $handle = fopen($filename, "r");
                $contents = fread($handle, filesize($filename));
                $DecryptedData=$this->decryptData($contents);
                fclose($handle);
                
                //$str = file_get_contents($destinationPath.'acta.json');
                $json = json_decode($DecryptedData, true);

                $v = Validator::make($json, $reglas_acta, $mensajes);
                if ($v->fails()) {
                    Storage::deleteDirectory('imports/'.$user_email.'/');
                    return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                }

                $json['empresa_clave'] = $json['empresa'];
                $folio_array = explode('/', $json['folio']);
                $json['clues'] = $folio_array[0];

                DB::beginTransaction();

                //$json['firma_solicita'] = $json['requisiciones'][0]['firma_solicita'];
                //$json['cargo_solicita'] = $json['requisiciones'][0]['cargo_solicita'];

                $acta = Acta::create($json);

                foreach ($json['requisiciones'] as $inputs_requisicion) {
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
                    }

                    //$max_requisicion = Requisicion::max('numero');
                    //if(!$max_requisicion){
                        //$max_requisicion = 0;
                    //}
                    //$inputs_requisicion['numero'] = $max_requisicion+1;
                    //$inputs_requisicion['empresa_clave'] = $inputs_requisicion['empresa'];
                    $inputs_requisicion['sub_total_validado'] = $inputs_requisicion['sub_total'];
                    $inputs_requisicion['gran_total_validado'] = $inputs_requisicion['gran_total'];
                    $inputs_requisicion['iva_validado'] = $inputs_requisicion['iva'];

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

                    if(isset($inputs_requisicion['insumos_clues'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos_clues'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['id'],
                                'clues' => $req_insumo['pivot']['clues'],
                                'cantidad' => $req_insumo['pivot']['cantidad'],
                                'total' => $req_insumo['pivot']['total'],
                                'cantidad_validada' => $req_insumo['pivot']['cantidad'],
                                'total_validado' => $req_insumo['pivot']['total']
                            ];
                        }
                        $requisicion->insumosClues()->sync($insumos);
                    }
                }

                DB::commit();

                Storage::deleteDirectory('imports/'.$user_email.'/');

                return Response::json([ 'data' => $json ],200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory('imports/'.$user_email.'/');
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update($id){
        $mensajes = [
            'required'      => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'num_oficio'        =>'required|unique:actas,num_oficio,'.$id,
            'fecha_solicitud'   =>'required|date',
            'lugar_entrega'     =>'required',
            'estatus'           =>'required'
        ];

        //$inputs = Input::all();

        try {

            $inputs = Input::all();

            $v = Validator::make($inputs, $reglas_acta, $mensajes);
            if ($v->fails()) {
                return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
            }

            $acta = Acta::find($id);

            if($acta->estatus >= 3){
                throw new \Exception("El Acta no se puede editar ya que se encuentra con estatus de enviada");
            }

            if($inputs['estatus'] == 3){
                $acta->estatus = 3;
                $acta->fecha_validacion = new DateTime();

                $acta->load('requisiciones');
                $validados = 0;
                $requisiciones = count($acta->requisiciones);
                foreach ($acta->requisiciones as $requisicion) {
                    if($requisicion->estatus === 1){
                        $validados++;
                    }
                }
                if($validados != $requisiciones){
                    return Response::json(['error' => 'Se necesita validar todas las requisiciones asignadas.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
            }

            DB::beginTransaction();

            $acta->num_oficio = $inputs['num_oficio'];
            $acta->lugar_entrega = $inputs['lugar_entrega'];
            $acta->fecha_solicitud = $inputs['fecha_solicitud'];

            if(!$acta->save()){
                throw new Exception("Ocurrió un error al intenar guardar los datos del acta", 1);
            }

            DB::commit();
            return Response::json([ 'data' => $acta ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
