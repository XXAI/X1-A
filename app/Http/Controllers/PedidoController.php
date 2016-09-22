<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Traits\SyncTrait;
use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\UnidadMedica;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \Font_Metrics, \PDF, \Storage, \ZipArchive, DateTime;

class PedidoController extends Controller
{
    use SyncTrait;
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

            $recurso = Acta::where('estatus','>=',3);

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

            $totales = $recurso->count();
            
            $recurso = $recurso->with('UnidadMedica','requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)
                                ->take($elementos_por_pagina)
                                ->orderBy('estatus','asc')
                                ->orderBy('fecha_termino','desc')
                                ->orderBy('estatus_sincronizacion','asc')
                                ->get();

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
        $proveedores = Proveedor::all();
        $max_oficio = Acta::max('num_oficio_pedido');
        if(!$max_oficio){
            $max_oficio = 0;
        }
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            },'requisiciones.insumos'=>function($query){
                $query->wherePivot('cantidad_validada','>',0)
                    ->orderBy('lote');
            }, 'unidadMedica'])->find($id);

        return Response::json([ 'data' => $acta, 'proveedores'=>$proveedores ,'oficio'=> $max_oficio+1 ],200);
    }

    public function generarNotificacionPDF($id){
        $data = [];
        //$acta = Acta::with('requisiciones.insumos','proveedores')->find($id);
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            },'requisiciones.insumos'=>function($query){
                $query->wherePivot('cantidad_validada','>',0);
            },'proveedores'
        ])->find($id);

        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        //$fecha = explode('-',$acta->fecha_pedido);

        if($acta->fecha_pedido){
            $fecha = explode('-',$acta->fecha_pedido);
        }else{
            $fecha = date('YYYY-m-d');
            $fecha = explode('-',$fecha);
        }
        $fecha[1] = $meses[$fecha[1]];
        $acta->fecha_pedido = $fecha;

        $num_oficio_proveedores = $acta->proveedores()->lists('num_oficio','proveedor_id');
        
        $empresas = Empresa::all();

        $partidas_presupuestarias = $empresas->lists('partida_presupuestal','pedido');
        $datos_proveedores = Proveedor::all();

        $proveedores = [];
        foreach ($acta->requisiciones as $requisicion) {
            foreach ($requisicion->insumos as $insumo) {
                if($insumo->pivot->proveedor_id){
                    if(!isset($proveedores[$insumo->pivot->proveedor_id])){
                        $proveedores[$insumo->pivot->proveedor_id] = [
                            'id'=>$insumo->pivot->proveedor_id,
                            'nombre' => '',
                            'direccion' => '',
                            'ciudad' => '',
                            'telefono' => '',
                            'num_oficio' => 0,
                            'pedidos' => [],
                            'partidas' => [],
                            'requisiciones' => []
                        ];
                    }
                    if(!isset($proveedores[$insumo->pivot->proveedor_id]['pedidos'][$requisicion->pedido])){
                        $proveedores[$insumo->pivot->proveedor_id]['pedidos'][$requisicion->pedido] = $requisicion->pedido;
                        $proveedores[$insumo->pivot->proveedor_id]['partidas'][$partidas_presupuestarias[$requisicion->pedido]] = true;

                    }
                    if(!isset($proveedores[$insumo->pivot->proveedor_id]['requisiciones'][$requisicion->numero])){
                        $proveedores[$insumo->pivot->proveedor_id]['requisiciones'][$requisicion->numero] = true;
                    }
                }
            }
        }
        foreach ($datos_proveedores as $proveedor) {
            if(isset($proveedores[$proveedor->id])){
                $proveedores[$proveedor->id]['nombre'] = $proveedor->nombre;
                $proveedores[$proveedor->id]['direccion'] = $proveedor->direccion;
                $proveedores[$proveedor->id]['ciudad'] = $proveedor->ciudad;
                $proveedores[$proveedor->id]['telefono'] = $proveedor->telefono;
                $proveedores[$proveedor->id]['num_oficio'] = $num_oficio_proveedores[$proveedor->id];

                $partidas = array_keys($proveedores[$proveedor->id]['partidas']);
                $texto_partidas = '';
                if(count($partidas) > 2){
                    $separador = '';
                    for ($i=0; $i < count($partidas)-1 ; $i++) { 
                        $texto_partidas .= $separador . $partidas[$i];
                        $separador = ', ';
                    }
                    $texto_partidas .= ' y ' . $partidas[count($partidas)-1];
                }elseif(count($partidas) > 1){
                    $texto_partidas = $partidas[0] . ' y ' . $partidas[1];
                }else{
                    $texto_partidas = $partidas[0];
                }
                $proveedores[$proveedor->id]['partidas'] = $texto_partidas;

                $requisiciones = array_keys($proveedores[$proveedor->id]['requisiciones']);
                $texto_requisiciones = '';
                if(count($requisiciones) > 2){
                    $separador = '';
                    for ($i=0; $i < count($requisiciones)-1 ; $i++) { 
                        $texto_requisiciones .= $separador . $requisiciones[$i];
                        $separador = ', ';
                    }
                    $texto_requisiciones .= ' y ' . $requisiciones[count($requisiciones)-1];
                }elseif(count($requisiciones) > 1){
                    $texto_requisiciones = $requisiciones[0] . ' y ' . $requisiciones[1];
                }else{
                    $texto_requisiciones = $requisiciones[0];
                }
                $proveedores[$proveedor->id]['requisiciones'] = $texto_requisiciones;
            }
        }
        $data['proveedores'] = $proveedores;
        $data['acta'] = $acta;
        $data['configuracion'] = Configuracion::find(1);
        //return Response::json(['data' => $data], 200);

        $pdf = PDF::loadView('pdf.notificacion', $data);
        return $pdf->stream('Notificaciones-'.$acta->folio.'.pdf');
    }

    public function generarPedidoPDF($id){
        $data = [];
        //$acta = Acta::with('requisiciones.insumos','proveedores')->find($id);
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            },'requisiciones.insumos'=>function($query){
                $query->wherePivot('cantidad_validada','>',0)
                    ->orderBy('lote');
            },'proveedores'
        ])->find($id);

        $num_oficio_proveedores = $acta->proveedores()->lists('num_oficio','proveedor_id');
        /*if(!$requisicion->estatus){
            return Response::json(['error' => 'No se puede generar el archivo por que la requisición no se encuentra aprobada'], HttpResponse::HTTP_CONFLICT);
        }*/

        //$unidad = UnidadMedica::where('clues',$acta->clues)->first();
        $empresa = Empresa::where('clave',$acta->empresa_clave)->first();

        $configuracion = Configuracion::find(1);

        $proveedores = Proveedor::lists('nombre','id');
        
        $pedidos = [];
        foreach ($acta->requisiciones as $requisicion) {
            if(!isset($pedidos[$requisicion->pedido])){
                $pedidos[$requisicion->pedido] = [];
            }
            $pedido = [];
            foreach ($requisicion->insumos as $insumo) {
                if($insumo->pivot->proveedor_id){
                    if(!isset($pedido[$insumo->pivot->proveedor_id])){
                        $pedido[$insumo->pivot->proveedor_id] = [
                            'oficio' => $num_oficio_proveedores[$insumo->pivot->proveedor_id],
                            'pedido' => $requisicion->pedido,
                            'tipo_requisicion' => $requisicion->tipo_requisicion,
                            'proveedor' => $proveedores[$insumo->pivot->proveedor_id],
                            'proveedor_id' => $insumo->pivot->proveedor_id,
                            'no_requisicion' => $requisicion->numero,
                            'lugar_entrega' => $acta->lugar_entrega,
                            'sub_total' => 0,
                            'iva' => 0,
                            'gran_total' => 0,
                            'total_letra' => '',
                            'fuente_financiamiento' => '',
                            'insumos' => []
                        ];
                        if($requisicion->tipo_requisicion == 2){
                            $pedido[$insumo->pivot->proveedor_id]['fuente_financiamiento'] = 'FASSA';
                        }else{
                            $pedido[$insumo->pivot->proveedor_id]['fuente_financiamiento'] = 'REPSS';
                        }
                    }
                    $pedido[$insumo->pivot->proveedor_id]['insumos'][] = $insumo->toArray();

                    $pedido[$insumo->pivot->proveedor_id]['sub_total'] += $insumo->pivot->total_validado;
                    if($requisicion->tipo_requisicion == 3){
                        $pedido[$insumo->pivot->proveedor_id]['iva'] += $insumo->pivot->total_validado*16/100;
                        $iva = $insumo->pivot->total_validado*16/100;
                    }else{
                        $iva = 0;
                    }
                    $pedido[$insumo->pivot->proveedor_id]['gran_total'] += $iva + $insumo->pivot->total_validado;
                }
            }
            foreach ($pedido as $index => $proveedor) {
                $pedido[$index]['total_letra'] = $this->transformarCantidadLetras($proveedor['gran_total']);
            }
            $pedidos[$requisicion->pedido][] = $pedido;
        }
        //var_dump($pedidos);die;
        //return Response::json([ 'data' => $pedidos ],200);
        $data['empresa'] = $empresa;
        $data['pedidos'] = $pedidos;
        $data['estatus'] = $acta->estatus;
        $data['oficio_area_medica'] = $acta->num_oficio;
        $data['configuracion'] = $configuracion;

        $pdf = PDF::loadView('pdf.pedido', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-40), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));

        return $pdf->stream('Pedido-'.$acta->folio.'.pdf');
    }

    public function transformarCantidadLetras($cantidad){
        $cantidad = number_format($cantidad, 2, '.', '');
        $numeros = explode('.',$cantidad);
        
        $enteros = number_format($numeros[0]);
        
        $enteros_array = explode(',',$enteros);
        
        $total = count($enteros_array);
        $numero_letras = "";
        

        $miles = array();
        switch($total){
            case 2 :
                        $miles[0][0] = " MIL"; 
                        $miles[0][1] = " MIL"; 
                    break;
            case 3 :
                        $miles[0][0] = " MILLÓN";
                        $miles[0][1] = " MILLONES"; 
                        $miles[1][0] = " MIL";
                        $miles[1][1] = " MIL";
                    break;
            case 4 : 
                        $miles[0][0] = " MIL"; 
                        $miles[0][1] = " MIL";
                        $miles[1][0] = " MILLON"; 
                        $miles[1][1] = " MILLONES";
                        $miles[2][0] = " MIL"; 
                        $miles[2][1] = " MIL";
                    break;
            case 5 : 
                        $miles[0][0] = " BILLON"; 
                        $miles[0][1] = " BILLONES";
                        $miles[1][0] = " MIL"; 
                        $miles[1][1] = " MIL";
                        $miles[2][0] = " MILLON"; 
                        $miles[2][1] = " MILLONES";
                        $miles[3][0] = " MIL"; 
                        $miles[3][1] = " MIL";
                    break;
            case 6 : 
                        $miles[0][0] = " MIL"; 
                        $miles[0][1] = " MIL";
                        $miles[1][0] = " BILLON"; 
                        $miles[1][1] = " BILLONES";
                        $miles[2][0] = " MIL"; 
                        $miles[2][1] = " MIL";
                        $miles[3][0] = " MILLON"; 
                        $miles[3][1] = " MILLONES";
                        $miles[4][0] = " MIL"; 
                        $miles[4][1] = " MIL";
                    break;
            
        }

        $moneda = ' PESOS ';
        if($total > 2 && $enteros_array[1] == 0  && $enteros_array[2] == 0){
            $moneda = ' DE PESOS ';
        }

        for($i=0;$i<$total;$i++){
            $anexo = "";
            if($i==$total-1){
                $centesimos=1;
            }               
            else{
                $centesimos=1;              
                
                if($enteros_array[$i] > 0){
                    if($enteros_array[$i]>1){
                        $anexo = $miles[$i][1];
                    }else{
                        $anexo = $miles[$i][0];
                    }
                }
            }
                
            $numero_letras.= self::regresa_letras($enteros_array[$i],$centesimos).$anexo;
        }
        return $numero_letras.$moneda.$numeros[1]."/100";
    }

    private function regresa_letras($numeros, $centesimo){
        $cadena_enteros = "";
        $total = strlen($numeros);
        
        if($total==2)
            $numeros = "0".$numeros;
        if($total==1)
            $numeros = "00".$numeros;
        
        $enteros = str_split($numeros);
        
        $total = count($enteros);
        
        for($i=0;$i<$total;$i++){
            switch($i){
                case 0: 
                        $decimo = $enteros[$i+1] . $enteros[$i+2];
                        switch((int)$enteros[$i]){
                            case 1: 
                                    if($decimo=='00')
                                        $cadena_enteros.=" CIEN";
                                    else
                                        $cadena_enteros.=" CIENTO";
                                break;
                            case 2: $cadena_enteros.=" DOSCIENTOS"; break;
                            case 3: $cadena_enteros.=" TRESCIENTOS"; break;
                            case 4: $cadena_enteros.=" CUATROCIENTOS"; break;
                            case 5: $cadena_enteros.=" QUINIENTOS"; break;
                            case 6: $cadena_enteros.=" SEISCIENTOS"; break;
                            case 7: $cadena_enteros.=" SETECIENTOS"; break;
                            case 8: $cadena_enteros.=" OCHOCIENTOS"; break;
                            case 9: $cadena_enteros.=" NOVECIENTOS"; break;
                        }
                        
                    
                    break;
                case 1: 
                        $natural = $enteros[$i+1];
                    
                        switch((int)$enteros[$i])
                        {
                            case 1:                                 
                                    switch((int)$natural)
                                    {
                                        case 0: $cadena_enteros.=" DIEZ"; break;
                                        case 1: $cadena_enteros.=" ONCE"; break;
                                        case 2: $cadena_enteros.=" DOCE"; break;
                                        case 3: $cadena_enteros.=" TRECE"; break;
                                        case 4: $cadena_enteros.=" CATORCE"; break;
                                        case 5: $cadena_enteros.=" QUINCE"; break;
                                        case 6: $cadena_enteros.=" DIECISEIS"; break;
                                        case 7: $cadena_enteros.=" DIECISIETE"; break;
                                        case 8: $cadena_enteros.=" DIECIOCHO"; break;
                                        case 9: $cadena_enteros.=" DIECINUEVE"; break;
                                    }
                                    //En este caso finalizamos el cilclo de una vez
                                    $i++;
                                        
                                    
                                break;
                            case 2:
                                    switch((int)$natural)
                                    {
                                        case 0: $cadena_enteros.=" VEINTE"; break;
                                        case 1:
                                                if($centesimo>0)
                                                    $cadena_enteros.=" VEINTIUN"; 
                                                else
                                                    $cadena_enteros.=" VEINTIUNO"; 
                                                    break;
                                        case 2: $cadena_enteros.=" VEINTIDOS"; break;
                                        case 3: $cadena_enteros.=" VEINTITRÉS"; break;
                                        case 4: $cadena_enteros.=" VEINTICUATRO"; break;
                                        case 5: $cadena_enteros.=" VEINTICINCO"; break;
                                        case 6: $cadena_enteros.=" VEINTISEIS"; break;
                                        case 7: $cadena_enteros.=" VEINTISIETE"; break;
                                        case 8: $cadena_enteros.=" VEINTIOCHO"; break;
                                        case 9: $cadena_enteros.=" VEINTINUEVE"; break;
                                    }
                                    //En este caso finalizamos el cilclo de una vez
                                    $i++;
                                        
                                    
                                break;
                            case 3: 
                                    $cadena_enteros.=" TREINTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                            case 4: $cadena_enteros.=" CUARENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                            case 5: $cadena_enteros.=" CINCUENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                            case 6: $cadena_enteros.=" SESENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                        
                                    break;
                            case 7: $cadena_enteros.=" SETENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                            case 8: $cadena_enteros.=" OCHENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                            case 9: $cadena_enteros.=" NOVENTA"; 
                                    if($natural!=0)
                                        $cadena_enteros.= " Y";
                                    else
                                    {
                                        //Finalizamos
                                        $i++;
                                    }
                                    break;
                        }
                        
                    
                    break;
                case 2:
                        switch((int)$enteros[$i])
                        {
                            case 0: 
                                    if($total==1)
                                        $cadena_enteros.=" CERO"; 
                                    break;
                            case 1:
                                    if($centesimo>0)
                                        $cadena_enteros.=" UN"; 
                                    else
                                        $cadena_enteros.=" UNO"; 
                                    break;
                            case 2: $cadena_enteros.=" DOS"; break;
                            case 3: $cadena_enteros.=" TRES"; break;
                            case 4: $cadena_enteros.=" CUATRO"; break;
                            case 5: $cadena_enteros.=" CINCO"; break;
                            case 6: $cadena_enteros.=" SEIS"; break;
                            case 7: $cadena_enteros.=" SIETE"; break;
                            case 8: $cadena_enteros.=" OCHO"; break;
                            case 9: $cadena_enteros.=" NUEVE"; break;
                        }
                        
                    break;
            }
        }
        return $cadena_enteros;
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
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            //'num_oficio_pedido'         =>'required|unique:acta_proveedor,num_oficio,'.$id.',acta_id',
            'fecha_pedido'              =>'required|date',
            //'fuente_financiamiento'     =>'required',
            'estatus'                   =>'required'
        ];

        //$inputs = Input::all();

        try {
            $inputs = Input::all();

            $v = Validator::make($inputs, $reglas_acta, $mensajes);
            if ($v->fails()) {
                return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
            }

            //$acta = Acta::with('requisiciones.insumos')->find($id);
            $acta = Acta::with([
                'requisiciones'=>function($query){
                    $query->where('gran_total_validado','>',0);
                },'requisiciones.insumos'=>function($query){
                    $query->wherePivot('cantidad_validada','>',0);
                }
            ])->find($id);

            if($acta->estatus >= 4){
                throw new \Exception("El Acta no se puede editar ya que se encuentra con estatus de validada");
            }

            DB::beginTransaction();

            $estatus_anterior = $acta->estatus;

            $acta->fecha_pedido = $inputs['fecha_pedido'];
            //$acta->num_oficio_pedido = $inputs['num_oficio_pedido'];
            //$acta->fuente_financiamiento = $inputs['fuente_financiamiento'];
            $acta->estatus = $inputs['estatus'];

            if($inputs['estatus'] == 4){
                $acta->fecha_termino = new DateTime();
                $max_oficio = Acta::max('num_oficio_pedido');
                if(!$max_oficio){
                    $max_oficio = 0;
                }
                $acta->num_oficio_pedido = ($max_oficio + 1);
            }

            $lista_proveedores = [];

            if($acta->save()){
                if($estatus_anterior == 3){
                    $requisiciones = $inputs['requisiciones'];
                    $lista_insumos = [];
                    $proveedores_faltantes = 0;
                    foreach ($requisiciones as $requisicion) {
                        $lista_insumos[$requisicion['id']] = [];
                        foreach ($requisicion['insumos'] as $insumo) {
                            if(isset($insumo['proveedor_id'])){
                                $lista_insumos[$requisicion['id']][$insumo['insumo_id']] = $insumo['proveedor_id'];
                            }else{
                                $lista_insumos[$requisicion['id']][$insumo['insumo_id']] = null;
                                $proveedores_faltantes++;
                            }
                        }
                    }

                    if($proveedores_faltantes > 0 && $inputs['estatus'] == 4){
                        DB::rollBack();
                        return Response::json([ 'error' => 'No todos los proveedores han sido asignados', 'error_type' => 'data_validation' ],500);
                    }

                    foreach ($acta->requisiciones as $requisicion) {
                        $insumos_form = $lista_insumos[$requisicion->id];
                        $insumos_sync = [];
                        
                        foreach ($requisicion->insumos as $req_insumo) {
                            $insumos_sync[] = [
                                'insumo_id' => $req_insumo->pivot->insumo_id,
                                'cantidad' => $req_insumo->pivot->cantidad,
                                'total' => $req_insumo->pivot->total,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'proveedor_id' => (isset($insumos_form[$req_insumo->pivot->insumo_id]))?$insumos_form[$req_insumo->pivot->insumo_id]:null
                            ];
                            if(isset($insumos_form[$req_insumo->pivot->insumo_id])){
                                if(!isset($lista_proveedores[$insumos_form[$req_insumo->pivot->insumo_id]])){
                                    $lista_proveedores[$insumos_form[$req_insumo->pivot->insumo_id]] = true;
                                }
                            }
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos_sync);
                    }
                    
                    if(count($lista_proveedores)){
                        if($inputs['estatus'] == 4){
                            $oficio_consecutivo = $acta->num_oficio_pedido;
                        }else{
                            $oficio_consecutivo = 0;
                        }
                        $proveedores_sync = [];
                        foreach ($lista_proveedores as $proveedor_id => $valor) {
                            $proveedores_sync[] =[
                                'acta_id' => $acta->id,
                                'proveedor_id' => $proveedor_id,
                                'num_oficio' => $oficio_consecutivo
                            ];
                            if($inputs['estatus'] == 4){
                                $oficio_consecutivo++;
                            }
                        }
                        $acta->proveedores()->sync([]);
                        $acta->proveedores()->sync($proveedores_sync);

                        if($inputs['estatus'] == 4){
                            $acta->num_oficio_pedido = ($oficio_consecutivo-1);
                            $acta->save();
                        }
                    }
                }
            }else{
                throw new Exception("Ocurrió un error al intenar guardar los datos del acta", 1);
            }

            DB::commit();

            if($acta->estatus == 4){
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
            if($acta->estatus >= 4){
                //DB::rollBack();
                $resultado = $this->actualizarUnidades($acta->folio);
                if(!$resultado['estatus']){
                    return Response::json(['error' => 'Error al intentar sincronizar el acta', 'error_type' => 'data_validation', 'message'=>$resultado['message']], HttpResponse::HTTP_CONFLICT);
                }
                $acta = Acta::find($id);
            }else{
                return Response::json(['error' => 'El acta no puede ser sincronizada en este modulo.', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
            }
            return Response::json([ 'data' => $acta ],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
