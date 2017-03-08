<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Traits\SyncTrait;
use App\Http\Requests;
use App\Models\Acta;
use App\Models\Entrada;
use App\Models\StockInsumo;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\UnidadMedica;
use App\Models\Empresa;
use App\Models\Usuario;
use \Excel;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, Exception;

class RecepcionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{
            DB::enableQueryLog();
            
            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
            $filtro = Input::get('filtro');

            $recurso = Acta::select('actas.*')->join('entradas','entradas.acta_id','=','actas.id');

            if($query){
                if(is_numeric($query)){
                    $actas = Requisicion::where ('numero',intval($query))->lists('acta_id');
                    $recurso = $recurso->whereIn('actas.id',$actas);
                }else{
                    $recurso = $recurso->where(function($condition)use($query){
                        $condition->where('actas.folio','LIKE','%'.$query.'%')
                                ->orWhere('actas.lugar_reunion','LIKE','%'.$query.'%')
                                ->orWhere('actas.ciudad','LIKE','%'.$query.'%');
                    });
                }
            }

            if($filtro){
                if(isset($filtro['estatus'])){
                    if($filtro['estatus'] == 'nuevos'){
                        $recurso = $recurso->whereNull('actas.total_claves_recibidas');
                    }else if($filtro['estatus'] == 'incompletos'){
                        $recurso = $recurso->whereRaw('actas.total_claves_recibidas < actas.total_claves_validadas');
                    }else if($filtro['estatus'] == 'completos'){
                        $recurso = $recurso->whereRaw('actas.total_claves_validadas = actas.total_claves_recibidas');
                    }
                }
            }

            $totales = $recurso->select(DB::raw('count(distinct entradas.acta_id) as total'))->first();
            $totales = $totales->total;

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            
            $recurso = $recurso->select('actas.*')
                                ->with('requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->groupBy('actas.id')
                                ->orderBy('actas.estatus','asc')
                                ->orderBy('actas.created_at','desc')
                                ->get();

            
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
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
    public function show(Request $request, $id){
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->orderBy('tipo_requisicion');
            },'requisiciones.insumos'=>function($query){
                $query->where('cantidad_validada','>',0)->orderBy('lote');
            },'entradas.stock'])->find($id);

        $configuracion = UnidadMedica::where('clues',$acta->clues)->first();

        $proveedores = Proveedor::all()->lists('nombre','id');

        return Response::json([ 'data' => $acta, 'configuracion'=>$configuracion, 'proveedores' => $proveedores],200);
    }

    public function showEntrada(Request $request, $id){
        $entrada = Entrada::with('stock.insumo','acta.empresa')->find($id);

        $proveedor_id = $entrada->proveedor_id;

        $entrada->acta->load(['requisiciones.insumos'=>function($query)use($proveedor_id){
            $query->select('id')->wherePivot('cantidad_recibida','>',0)->wherePivot('proveedor_id',$proveedor_id);
        }]);

        $configuracion = UnidadMedica::where('clues',$entrada->acta->clues)->first();

        $proveedor = Proveedor::find($proveedor_id);

        return Response::json([ 'data' => $entrada, 'configuracion'=>$configuracion, 'proveedor' => $proveedor],200);
    }
    
    public function generarExcel($id) {
        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];
        $data['acta'] = Acta::with([
            'requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            },'requisiciones.insumos'=>function($query){
                $query->wherePivot('total_validado','>',0)
                    ->orderBy('lote');
            }
        ])->find($id);

        if($data['acta']->estatus <= 3){
            return Response::json(['error' =>'El acta seleccionada no se encuentra en un estatus valido', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
        }

        $configuracion = UnidadMedica::where('clues',$data['acta']->clues)->first();

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        $data['unidad'] = mb_strtoupper($configuracion->nombre,'UTF-8');
        $empresa = Empresa::where('clave','=',$data['acta']->empresa_clave)->first();
        $data['empresa'] = $empresa->nombre;
        $data['empresa_clave'] = $data['acta']->empresa_clave;

        $nombre_archivo = 'Avance Recepcion ' . str_replace("/","-",$data['acta']->folio);  

        Excel::create($nombre_archivo, function($excel) use($data) {
            $unidad = $data['unidad'];
            $acta = $data['acta'];
            $requisiciones = $acta->requisiciones;

            $acumulado_requisicion = []; //por tipo de requisicion = [claves_pedidas,claves_recibidas,cantidad_pedida,cantidad_recibida,total_pedido,total_recibido]

            foreach($requisiciones as $index => $requisicion) {
                $tipo  = '';
                switch($requisicion->tipo_requisicion) {
                    case 1: $tipo = "MEDICAMENTOS CAUSES"; break;
                    case 2: $tipo = "MEDICAMENTOS NO CAUSES"; break;
                    case 3: $tipo = "MATERIAL DE CURACION"; break;
                    case 4: $tipo = "MEDICAMENTOS CONTROLADOS"; break;
                    case 5: $tipo = "FACTOR SURFACTANTE (CAUSES)"; break;
                    case 6: $tipo = "FACTOR SURFACTANTE (NO CAUSES)"; break;
                }

                $acumulado_requisicion[$requisicion->tipo_requisicion] = [
                    'claves_pedidas' => 0,
                    'claves_recibidas' => 0,
                    'cantidad_pedida' => 0,
                    'cantidad_recibida' => 0,
                    'total_pedido' => 0,
                    'total_recibido' => 0
                ];

                foreach($requisicion->insumos as $indice => $insumo){
                    $acumulado_requisicion[$requisicion->tipo_requisicion]['claves_pedidas'] += 1;
                    $acumulado_requisicion[$requisicion->tipo_requisicion]['cantidad_pedida'] += $insumo['pivot']['cantidad_validada'];
                    $acumulado_requisicion[$requisicion->tipo_requisicion]['total_pedido'] += $insumo['pivot']['total_validado'];

                    if($insumo['pivot']['cantidad_recibida']){
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['claves_recibidas'] += 1;
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['cantidad_recibida'] += $insumo['pivot']['cantidad_recibida'];
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['total_recibido'] += $insumo['pivot']['total_recibido'];
                    }
                }
                
                $excel->sheet($tipo, function($sheet) use($requisicion,$acta,$unidad) {
                        $sin_validar = '';
                        if($acta->estatus < 3 ) {$sin_validar = " (SIN VALIDAR)";}
                        $sheet->setAutoSize(true);

                        $sheet->mergeCells('A1:L1');
                        $sheet->row(1, array('ACTA: '.$acta->folio.$sin_validar));
                        //$sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                        $sheet->mergeCells('A2:L2'); 
                        $sheet->row(2, array('UNIDAD: '.$unidad));
                        //$sheet->row(2, array('REQUISICIÓN NO.: '.$requisicion->numero));

                        $sheet->mergeCells('A3:L3'); 
                        $sheet->row(3, array('PEDIDO: '.$requisicion->pedido));

                        $sheet->mergeCells('A4:L4'); 
                        $sheet->row(4, array('No. DE REQUISICIÓN: '.$requisicion->numero));
                        

                        $sheet->mergeCells('A5:L5'); 
                        $sheet->row(5, array('FECHA: '.$acta->fecha[2]." DE ".$acta->fecha[1]." DEL ".$acta->fecha[0]));

                        $sheet->mergeCells('A6:L6');
                        $sheet->row(6, array(''));

                        $sheet->row(7, array(
                            'No. DE LOTE', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','PRECIO UNITARIO','CANTIDAD PEDIDA', 'CANTIDAD RECIBIDA', 'CANTIDAD RESTANTE','%','TOTAL PEDIDO', 'TOTAL RECIBIDO','TOTAL RESTANTE', '%'
                        ));
                        $sheet->row(1, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(16);
                        });

                        $sheet->row(2, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(14);
                        });
                         $sheet->row(3, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(14);
                        });
                         $sheet->row(4, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(14);
                        });

                        $sheet->row(5, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(14);
                        });

                        $sheet->row(6, function($row) {
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');
                            $row->setFontSize(14);
                        });

                        $sheet->row(7, function($row) {
                            // call cell manipulation methods
                            $row->setBackground('#DDDDDD');
                            $row->setFontWeight('bold');

                        });

                        $contador_filas = 7;
                        foreach($requisicion->insumos as $indice => $insumo){
                            $contador_filas += 1;

                            $sheet->appendRow(array(
                                $insumo['lote'], 
                                $insumo['clave'],
                                $insumo['descripcion'],
                                $insumo['precio'],
                                $insumo['pivot']['cantidad_validada'],
                                $insumo['pivot']['cantidad_recibida'],
                                "=E$contador_filas-F$contador_filas",
                                "=F$contador_filas/E$contador_filas",
                                $insumo['pivot']['total_validado'],
                                $insumo['pivot']['total_recibido'],
                                "=I$contador_filas-J$contador_filas",
                                "=J$contador_filas/I$contador_filas"
                            ));
                        }
                        
                        $sheet->appendRow(array(
                                '', 
                                '',
                                '',
                                'SUBTOTAL',
                                '',
                                '',
                                '',
                                '',
                                '=SUM(I8:I'.($contador_filas).')',
                                '=SUM(J8:J'.($contador_filas).')',
                                '=SUM(K8:K'.($contador_filas).')',
                                ''
                            ));
                    

                        if($requisicion->tipo_requisicion == 3){
                            $iva_pedido = '=I'.($contador_filas+1).'*16/100';
                            $iva_recibido = '=J'.($contador_filas+1).'*16/100';
                            $iva_restante = '=K'.($contador_filas+1).'*16/100';
                        }else{
                            $iva_pedido = 0;
                            $iva_recibido = 0;
                            $iva_restante = 0;
                        }
                        $sheet->appendRow(array(
                                '', 
                                '',
                                '',
                                'IVA',
                                '',
                                '',
                                '',
                                '',
                                $iva_pedido,
                                $iva_recibido,
                                $iva_restante,
                                ''
                            ));
                        $sheet->appendRow(array(
                                '', 
                                '',
                                '',
                                'TOTAL',
                                '=SUM(E8:E'.($contador_filas).')',
                                '=SUM(F8:F'.($contador_filas).')',
                                '=SUM(G8:G'.($contador_filas).')',
                                '=F'.($contador_filas+3).'/E'.($contador_filas+3),
                                '=SUM(I'.($contador_filas+1).':I'.($contador_filas+2).')',
                                '=SUM(J'.($contador_filas+1).':J'.($contador_filas+2).')',
                                '=SUM(K'.($contador_filas+1).':K'.($contador_filas+2).')',
                                '=J'.($contador_filas+3).'/I'.($contador_filas+3),
                            ));
                        
                        $contador_filas += 3;

                        $sheet->setBorder("A1:L$contador_filas", 'thin');


                        $sheet->cells("F1:H$contador_filas", function($cells) {
                            $cells->setAlignment('right');
                        });

                        $sheet->cells("A7:A$contador_filas", function($cells) {
                            $cells->setAlignment('center');
                        });
                        $sheet->cells("B7:B$contador_filas", function($cells) {
                            $cells->setAlignment('center');
                        });
                        $sheet->cells("E7:G$contador_filas", function($cells) {
                            $cells->setAlignment('center');
                        });

                        $phpColor = new \PHPExcel_Style_Color();
                        $phpColor->setRGB('DDDDDD'); 
                        $sheet->getStyle("H8:H$contador_filas")->getFont()->setColor( $phpColor );
                        $sheet->getStyle("L8:L$contador_filas")->getFont()->setColor( $phpColor );

                        $sheet->setColumnFormat(array(
                            "D8:D$contador_filas" => '"$"#,##0.00_-',
                            "E8:G$contador_filas" => '#,##0_-',
                            "H8:H$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                            "I8:K$contador_filas" => '"$"#,##0.00_-',
                            "L8:L$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%'
                        ));

                        $sheet->freezePane('A8');
                });
            }

            $excel->sheet('RESUMEN POR REQUISICION', function($sheet) use($requisiciones,$acta,$unidad,$acumulado_requisicion){
                $sheet->setAutoSize(true);

                $sheet->mergeCells('A1:N1');
                $sheet->row(1, array('ACTA: '.$acta->folio));
                //$sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                $sheet->mergeCells('A2:N2'); 
                $sheet->row(2, array('UNIDAD: '.$unidad));
                //$sheet->row(2, array('REQUISICIÓN NO.: '.$requisicion->numero));

                $sheet->mergeCells('A3:N3'); 
                $sheet->row(3, array('FECHA: '.$acta->fecha[2]." DE ".$acta->fecha[1]." DEL ".$acta->fecha[0]));

                $sheet->mergeCells('A4:N4');
                $sheet->row(4, array(''));

                $sheet->row(5, array(
                        '', 'REQUISICION','CLAVES PEDIDAS', 'CLAVES RECIBIDAS', 'CLAVES RESTANTES','%','CANTIDAD PEDIDA', 'CANTIDAD RECIBIDA', 'CANTIDAD RESTANTE','%','TOTAL PEDIDO', 'TOTAL RECIBIDO','TOTAL RESTANTE', '%'
                    ));

                $sheet->row(1, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(16);
                });
                $sheet->row(2, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                 $sheet->row(3, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                 $sheet->row(4, function($row) {
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');
                    $row->setFontSize(14);
                });
                $sheet->row(5, function($row) {
                    // call cell manipulation methods
                    $row->setBackground('#DDDDDD');
                    $row->setFontWeight('bold');

                });

                $contador_filas = 5;
                foreach($requisiciones as $index => $requisicion) {
                    $contador_filas++;

                    $tipo  = '';
                    switch($requisicion->tipo_requisicion) {
                        case 1: $tipo = "MEDICAMENTOS CAUSES"; break;
                        case 2: $tipo = "MEDICAMENTOS NO CAUSES"; break;
                        case 3: $tipo = "MATERIAL DE CURACION"; break;
                        case 4: $tipo = "MEDICAMENTOS CONTROLADOS"; break;
                        case 5: $tipo = "FACTOR SURFACTANTE (CAUSES)"; break;
                        case 6: $tipo = "FACTOR SURFACTANTE (NO CAUSES)"; break;
                    }

                    $sheet->appendRow(array(
                        '', 
                        $tipo,
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['claves_pedidas'],
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['claves_recibidas'],
                        "=C$contador_filas-D$contador_filas",
                        "=D$contador_filas/C$contador_filas",
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['cantidad_pedida'],
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['cantidad_recibida'],
                        "=G$contador_filas-H$contador_filas",
                        "=H$contador_filas/G$contador_filas",
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['total_pedido'],
                        $acumulado_requisicion[$requisicion->tipo_requisicion]['total_recibido'],
                        "=K$contador_filas-L$contador_filas",
                        "=L$contador_filas/K$contador_filas"
                    ));
                }

                $sheet->appendRow(array(
                    '',
                    'TOTAL',
                    "=SUM(C6:C$contador_filas)",
                    "=SUM(D6:D$contador_filas)",
                    "=SUM(E6:E$contador_filas)",
                    "=D".($contador_filas+1)."/C".($contador_filas+1),
                    "=SUM(G6:G$contador_filas)",
                    "=SUM(H6:H$contador_filas)",
                    "=SUM(I6:I$contador_filas)",
                    "=H".($contador_filas+1)."/G".($contador_filas+1),
                    "=SUM(K6:K$contador_filas)",
                    "=SUM(L6:L$contador_filas)",
                    "=SUM(M6:M$contador_filas)",
                    "=L".($contador_filas+1)."/K".($contador_filas+1)
                ));
                $contador_filas++;

                $sheet->setBorder("A1:N$contador_filas", 'thin');

                $sheet->cells("B5:B$contador_filas", function($cells) {
                    $cells->setAlignment('left');
                });

                $phpColor = new \PHPExcel_Style_Color();
                $phpColor->setRGB('DDDDDD'); 
                $sheet->getStyle("F6:F$contador_filas")->getFont()->setColor( $phpColor );
                $sheet->getStyle("J6:J$contador_filas")->getFont()->setColor( $phpColor );
                $sheet->getStyle("N6:N$contador_filas")->getFont()->setColor( $phpColor );

                $sheet->setColumnFormat(array(
                    "C6:E$contador_filas" => '#,##0_-',
                    "F6:F$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                    "G6:I$contador_filas" => '#,##0_-',
                    "J6:J$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%',
                    "K6:M$contador_filas" => '"$"#,##0.00_-',
                    "N6:N$contador_filas" => '[Green]0.00%;[Red]-0.00%;0.00%'
                ));
            });

        })->export('xls');
    }
}
