<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Models\UnidadMedica;
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
                                'cantidad_validada' => $req_insumo['pivot']['cantidad'],
                                'total_validado' => $req_insumo['pivot']['total']
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
        return Response::json([ 'data' => Acta::with('requisiciones.insumos','unidadMedica')->find($id)], 200);
    }

    public function generarActaPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::with('requisiciones')->find($id);

        $empresa = Empresa::where('clave',$data['acta']->empresa_clave)->first();
        $clues = UnidadMedica::where('clues',$data['acta']->clues)->first();
        
        $pedidos = $data['acta']->requisiciones->lists('pedido')->toArray();
        if(count($pedidos) == 1){
            $data['acta']->requisiciones = $pedidos[0];
        }elseif(count($pedidos) == 2){
            $data['acta']->requisiciones = $pedidos[0] . ' y ' . $pedidos[1];
        }else{
            $data['acta']->requisiciones = $pedidos[0] . ', ' . $pedidos[1] . ' y ' . $pedidos[2];
        }

        $data['acta']->hora_inicio = substr($data['acta']->hora_inicio, 0,5);
        $data['acta']->hora_termino = substr($data['acta']->hora_termino, 0,5);

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        $data['unidad'] = $clues->nombre;
        $data['empresa'] = $empresa->nombre;
        $data['empresa_clave'] = $empresa->clave;
        
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
        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];
        $data['acta'] = Acta::with('requisiciones.insumos')->find($id);

        $empresa = Empresa::where('clave',$data['acta']->empresa_clave)->first();
        $clues = UnidadMedica::where('clues',$data['acta']->clues)->first();

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        $data['unidad'] = $clues->nombre;
        $data['empresa'] = $empresa->nombre;
        $data['empresa_clave'] = $empresa->clave;

        $pdf = PDF::loadView('pdf.requisiciones', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-40), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream($data['acta']->folio.'Requisiciones.pdf');
    }

    function encryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $text = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
       return $crypttext;
    }

    public function generarJSON($id){
        $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->find($id);

        if($acta->estatus < 3){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra validada'], HttpResponse::HTTP_CONFLICT);
        }

        Storage::makeDirectory("export");
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),json_encode($acta));

        $filename = storage_path()."/app/export/json.".str_replace('/','-', $acta->folio);
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        $EncryptedData=$this->encryptData($contents);
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),$EncryptedData);
        fclose($handle);

        $storage_path = storage_path();

        $zip = new ZipArchive();
        $zippath = $storage_path."/app/";
        $zipname = "acta.valida.".str_replace('/','-', $acta->folio).".zip";

        $zip_status = $zip->open($zippath.$zipname,ZIPARCHIVE::CREATE);

        if ($zip_status === true) {
            $zip->addFile(storage_path().'/app/export/json.'.str_replace('/','-', $acta->folio),'acta.json');
            $zip->close();
            Storage::deleteDirectory("export");
            
            ///Then download the zipped file.
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zipname);
            header('Content-Length: ' . filesize($zippath.$zipname));
            readfile($zippath.$zipname);
            Storage::delete($zipname);
            exit();
        }else{
            return Response::json(['error' => 'El archivo zip, no se encuentra'], HttpResponse::HTTP_CONFLICT);
        }
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
            //'num_oficio'        =>'required|unique:actas,num_oficio,'.$id,
            'fecha_solicitud'   =>'required|date',
            //'lugar_entrega'     =>'required',
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

            DB::beginTransaction();

            if($inputs['estatus'] == 3){
                $acta->estatus = 3;
                $acta->fecha_validacion = new DateTime();

                //Solo si no tiene número de oficio, le generamos uno (Para casos de revalidación)
                if(!$acta->num_oficio){
                    $max_oficio = Acta::max('num_oficio');
                    $acta->num_oficio = $max_oficio+1;
                }
                
                //Se obtiene el numero de requisición máximo
                $actas = Acta::where('clues',$acta->clues)->lists('id');
                $max_requisicion = Requisicion::whereIn('acta_id',$actas)->max('numero');
                if(!$max_requisicion){
                    $max_requisicion = 0;
                }

                //cargamos las requisiciones del acta
                $acta->load('requisiciones');
                $validados = 0;
                $requisiciones = count($acta->requisiciones);
                //Ciclo para checar si todas las requisiciones del acta ya fueron validadas
                foreach ($acta->requisiciones as $requisicion) {
                    //Si estatus es 1, ya fue validada
                    if($requisicion->estatus === 1){
                        $validados++;
                    }
                    //Si el total validado es mayor a cero, le podemos generar un número de requisición
                    if($requisicion->gran_total_validado > 0){
                        //Solo si no tiene numero de requisición, le generamos uno (Para casos de revalidación)
                        if(!$requisicion->numero){
                            $max_requisicion++;
                            $requisicion->numero = $max_requisicion;
                        }
                        if(!$requisicion->save()){
                            throw new Exception("Ocurrió un error al intenar guardar los datos de las requisiciones", 1);
                        }
                    }
                }
                if($validados != $requisiciones){
                    DB::rollBack();
                    return Response::json(['error' => 'Se necesita validar todas las requisiciones asignadas.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
            }

            //$acta->num_oficio = $inputs['num_oficio'];
            //$acta->lugar_entrega = $inputs['lugar_entrega'];
            $acta->fecha_solicitud = $inputs['fecha_solicitud'];

            if(!$acta->save()){
                throw new Exception("Ocurrió un error al intenar guardar los datos del acta", 1);
            }
            DB::commit();

            if($acta->estatus == 3){
                //DB::rollBack();
                $resultado = $this->actualizarUnidades($acta->folio);
                if(!$resultado['estatus']){
                    return Response::json(['error' => 'Error al intentar sincronizar el acta', 'error_type' => 'data_validation', 'message'=>$resultado['message']], HttpResponse::HTTP_CONFLICT);
                }
                $acta = Acta::find($id);
            }

            return Response::json([ 'data' => $acta ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function sincronizar($id){
        try {
            $acta = Acta::find($id);
            if(!$acta){
                return Response::json(['error' => 'Acta no encontrada.', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
            }
            if($acta->estatus >= 3){
                //DB::rollBack();
                $resultado = $this->actualizarUnidades($acta->folio);
                if(!$resultado['estatus']){
                    return Response::json(['error' => 'Error al intentar sincronizar el acta', 'error_type' => 'data_validation', 'message'=>$resultado['message']], HttpResponse::HTTP_CONFLICT);
                }
                $acta = Acta::find($id);
            }
            return Response::json([ 'data' => $acta ],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function actualizarUnidades($folio){
        try{
            $acta_central = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->where('folio',$folio)->first();

            $default = DB::getPdo(); // Default conn
            $secondary = DB::connection('mysql_sync')->getPdo();

            $stamp_validacion = new DateTime();

            DB::setPdo($secondary);
            DB::beginTransaction();

            //$conexion_remota = DB::connection('mysql_sync');
            //$conexion_remota->beginTransaction();

            $acta_unidad = new Acta();
            $acta_unidad = $acta_unidad->setConnection('mysql_sync');
            $acta_unidad = $acta_unidad->with('requisiciones.insumos','requisiciones.insumosClues')->where('folio',$folio)->first();

            $acta_unidad->fecha_validacion = $acta_central->fecha_validacion;
            $acta_unidad->estatus = $acta_central->estatus;
            $acta_unidad->estatus_sincronizacion = 2;
            $acta_unidad->sincronizado_validacion = $stamp_validacion;

            if($acta_unidad->save()){
                $requisiciones_unidad = $acta_unidad->requisiciones->lists('id','tipo_requisicion');

                $requisiciones_validadas = [];
                foreach ($acta_central->requisiciones as $requisicion) {
                    $tipo_requisicion = $requisicion->tipo_requisicion;

                    $requisiciones_validadas[$tipo_requisicion] = [
                        'estatus' => $requisicion->estatus,
                        'numero' => $requisicion->numero,
                        'sub_total_validado' => 0,
                        'sub_total' => 0,
                        'lotes' => 0,
                        'insumos' => [],
                        'insumos_clues' => []
                    ];

                    if(count($requisicion->insumos)){
                        $insumos = [];
                        foreach ($requisicion->insumos as $req_insumo) {
                            $insumos[$req_insumo->llave] = [
                                'insumo_id' => $req_insumo->id,
                                'cantidad' => $req_insumo->pivot->cantidad,
                                'total' => $req_insumo->pivot->total,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'proveedor_id' => $req_insumo->pivot->proveedor_id
                            ];
                            $requisiciones_validadas[$tipo_requisicion]['sub_total_validado'] += $req_insumo->pivot->total_validado;
                            $requisiciones_validadas[$tipo_requisicion]['sub_total'] += $req_insumo->pivot->total;
                        }
                        $requisiciones_validadas[$tipo_requisicion]['insumos'] = $insumos;
                        $requisiciones_validadas[$tipo_requisicion]['lotes'] = count($insumos);
                    }

                    if(count($requisicion->insumosClues)){
                        $insumos = [];
                        foreach ($requisicion->insumosClues as $req_insumo) {
                            $insumos[$req_insumo->llave .'.'.$req_insumo->pivot->clues] = [
                                'insumo_id' => $req_insumo->id,
                                'cantidad' => $req_insumo->pivot->cantidad,
                                'total' => $req_insumo->pivot->total,
                                'clues' => $req_insumo->pivot->clues,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado
                            ];
                        }
                        $requisiciones_validadas[$tipo_requisicion]['insumos_clues'] = $insumos;
                    }

                    //Crear requisiciones encontradas en la base de datos central pero no en las unidades.
                    if(!isset($requisiciones_unidad[$tipo_requisicion])){
                        $sub_total = $requisiciones_validadas[$tipo_requisicion]['sub_total'];
                        $sub_total_validado = $requisiciones_validadas[$tipo_requisicion]['sub_total_validado'];
                        if($tipo_requisicion == 3){
                            $iva = $sub_total*16/100;
                            $iva_validado = $sub_total_validado*16/100;
                        }else{
                            $iva = 0;
                            $iva_validado = 0;
                        }
                        $nueva_requisicion = new Requisicion();
                        $nueva_requisicion->numero              = $requisicion->numero;
                        $nueva_requisicion->estatus             = $requisicion->estatus;
                        $nueva_requisicion->pedido              = $requisicion->pedido;
                        $nueva_requisicion->lotes               = $requisiciones_validadas[$tipo_requisicion]['lotes'];
                        $nueva_requisicion->empresa             = $acta_unidad->empresa;
                        $nueva_requisicion->tipo_requisicion    = $requisicion->tipo_requisicion;
                        $nueva_requisicion->dias_surtimiento    = 15;
                        $nueva_requisicion->sub_total           = $sub_total;
                        $nueva_requisicion->gran_total          = $sub_total + $iva;
                        $nueva_requisicion->iva                 = $iva;
                        $nueva_requisicion->sub_total_validado  = $sub_total_validado;
                        $nueva_requisicion->gran_total_validado = $sub_total_validado + $iva_validado;
                        $nueva_requisicion->iva_validado        = $iva_validado;

                        $acta_unidad->requisiciones()->save($nueva_requisicion);

                        $nueva_requisicion->insumos()->sync($requisiciones_validadas[$tipo_requisicion]['insumos']);
                        $nueva_requisicion->insumosClues()->sync($requisiciones_validadas[$tipo_requisicion]['insumos_clues']);

                    }
                }

                foreach ($acta_unidad->requisiciones as $requisicion) {
                    if(isset($requisiciones_validadas[$requisicion->tipo_requisicion])){
                        $requisicion_import = $requisiciones_validadas[$requisicion->tipo_requisicion];

                        $requisicion->estatus = $requisicion_import['estatus'];
                        $requisicion->numero = $requisicion_import['numero'];
                        $requisicion->lotes = $requisicion_import['lotes'];
                        $requisicion->sub_total_validado = $requisicion_import['sub_total_validado'];
                        if($requisicion->tipo_requisicion == 3){
                            $requisicion->iva_validado = $requisicion->sub_total_validado*16/100;
                        }else{
                            $requisicion->iva_validado = 0;
                        }
                        $requisicion->gran_total_validado = $requisicion->sub_total_validado + $requisicion->iva_validado;

                        //check this out
                        if($requisicion->save()){
                            $insumos_sync = [];
                            foreach ($requisicion->insumos as $insumo) {
                                $nuevo_insumo = [
                                    'insumo_id' => $insumo->id,
                                    'cantidad' => $insumo->pivot->cantidad,
                                    'total' => $insumo->pivot->total
                                    //'cantidad_validada' => 0, //<- aqui para evitar los null
                                    //'total_validado' => 0 //<- aqui para evitar los null
                                ];
                                if(isset($requisicion_import['insumos'][$insumo->llave])){
                                    $insumo_import = $requisicion_import['insumos'][$insumo->llave];
                                    $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                    $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                                    $nuevo_insumo['proveedor_id'] = $insumo_import['proveedor_id'];
                                }
                                $insumos_sync[] = $nuevo_insumo;
                            }
                            $requisicion->insumos()->sync([]);
                            $requisicion->insumos()->sync($insumos_sync);

                            $insumos_clues_sync = [];
                            foreach ($requisicion->insumosClues as $insumo) {
                                $nuevo_insumo = [
                                    'insumo_id' => $insumo->id,
                                    'clues' => $insumo->pivot->clues,
                                    'cantidad' => $insumo->pivot->cantidad,
                                    'total' => $insumo->pivot->total
                                ];
                                if(isset($requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues])){
                                    $insumo_import = $requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues];
                                    $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                    $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                                }
                                $insumos_clues_sync[] = $nuevo_insumo;
                            }
                            $requisicion->insumosClues()->sync([]);
                            $requisicion->insumosClues()->sync($insumos_clues_sync);
                        }
                    }
                }
            }

            //$conexion_remota->commit();
            DB::commit();
            DB::setPdo($default);

            $acta_central->estatus_sincronizacion = 2;
            $acta_central->sincronizado_validacion = $stamp_validacion;
            $acta_central->save();

            return ['estatus'=>true];
            //return Response::json(['acta_central'=>$acta_central,'acta_unidad'=>$acta_unidad],200);
        }catch(\Exception $e){
            //$conexion_remota->rollback();
            DB::rollBack();
            DB::setPdo($default);
            return ['estatus'=>false,'message'=>$e->getMessage().'. line:'.$e->getLine()];
            //return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
