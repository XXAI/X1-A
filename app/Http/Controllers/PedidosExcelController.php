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

class PedidosExcelController extends Controller
{
     public function generar($id){
        
       
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            },'requisiciones.insumos'=>function($query){
                $query->wherePivot('cantidad_validada','>',0)
                    ->orderBy('lote');
            }
        ])->find($id);

        $proveedores = Proveedor::lists('nombre','id');
        
        $pedidos = [];
        
        foreach ($acta->requisiciones as $requisicion) {
            
            if(!isset($pedidos[$requisicion->pedido])){
                $pedidos[$requisicion->pedido] = [];
            }

            $pedido = [];

            // REcorremos los lotes o insumos
            foreach ($requisicion->insumos as $insumo) {
                if($insumo->pivot->proveedor_id){
                    if(!isset($pedido[$insumo->pivot->proveedor_id])){
                        $pedido[$insumo->pivot->proveedor_id] = [
                            
                            'pedido' => $requisicion->pedido,
                            'tipo_requisicion' => $requisicion->tipo_requisicion,
                            'proveedor' => $proveedores[$insumo->pivot->proveedor_id],
                            'no_requisicion' => $requisicion->numero,
                            'lugar_entrega' => $acta->lugar_entrega,
                            'sub_total' => 0,
                            'iva' => 0,
                            'gran_total' => 0,
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
            
            $pedidos[$requisicion->pedido][] = $pedido;
        }

        $nombre_archivo = str_replace("/","-",$acta->folio);  

        Excel::create($nombre_archivo, function($excel) use($pedidos,$acta) {
            foreach ( $pedidos as $no_pedido => $proveedores ){

                foreach($proveedores as $proveedor) { 
        
                    foreach ($proveedor as $pedido_proveedor){
                            //var_dump($pedido_proveedor);
                            // $pedido_proveedor['proveedor'] nombre del proveedor

                        $tipo_requisicion = "";
                        if($pedido_proveedor["tipo_requisicion"] == 1){
                            $tipo_requisicion = "CAUSES";
                        } else if ( $pedido_proveedor["tipo_requisicion"] == 2) {
                            $tipo_requisicion = "NO CAUSES";
                        } else if ( $pedido_proveedor["tipo_requisicion"] == 3) {
                            $tipo_requisicion = "MATERIAL DE CURACIÓN";
                        } else {
                            $tipo_requisicion = "CONTROLADOS";
                        }

                        $excel->sheet($pedido_proveedor['pedido']." ".$tipo_requisicion, function($sheet) use($pedido_proveedor,$acta) {

                           
                            $sheet->setAutoSize(true);

                            $sheet->mergeCells('A1:G1');
                            $sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                            $sheet->mergeCells('A2:G2'); 
                            $sheet->row(2, array('No. DE OFICIO DE SOLICITUD DEL ÁREA MÉDICA: '.$acta->num_oficio));

                            $sheet->mergeCells('A3:G3'); 
                            $sheet->row(3, array('ACTA: '.$acta->folio));

                            $sheet->mergeCells('A4:G4'); 
                            $sheet->row(4, array('LUGAR ENTREGA: '.$pedido_proveedor['lugar_entrega']));

                            $sheet->mergeCells('A5:G5'); 
                            $sheet->row(5, array('No. DE REQUISICIÓN: '.$pedido_proveedor['no_requisicion']));

                            $sheet->mergeCells('A6:G6'); 
                           
                            $sheet->row(6, array('FECHA: '.date('d/m/Y', strtotime($acta->fecha_termino))));
                            
                            $sheet->row(7, array(
                                'No. DE LOTE', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','CANTIDAD','UNIDAD DE MEDIDA','PRECIO UNITARIO','PRECIO TOTAL'
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
                            foreach ($pedido_proveedor['insumos'] as $insumo) {
                              
                                $sheet->appendRow(array(
                                    $insumo['lote'], 
                                    $insumo['clave'],
                                    $insumo['descripcion'],
                                    $insumo['pivot']['cantidad_validada'],
                                    $insumo['unidad'],
                                    $insumo['precio'],
                                    $insumo['pivot']['total_validado']
                                ));
                                $contador_filas += 1;
                            }
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'SUBTOTAL',
                                    $pedido_proveedor['sub_total']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'IVA',
                                    $pedido_proveedor['iva']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'TOTAL',
                                    $pedido_proveedor['gran_total']
                                ));

                            $contador_filas += 3;

                            $sheet->setBorder("A1:G$contador_filas", 'thin');


                            $sheet->cells("F1:G$contador_filas", function($cells) {

                                $cells->setAlignment('right');

                            });

                            $sheet->cells("A7:A$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("B7:B$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("D7:D$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });

                            $sheet->setColumnFormat(array(
                                "F8:G$contador_filas" => '"$"#,##0.00_-'
                            ));

                           
                            
                             

                        });

                        
                    }
                    
                }
                
                /*
                foreach ($proveedores as $id_proveedor => $pedido_proveedor){
                    echo "<br>".$id_proveedor;

                }*/

                
                        
            } 
        })->export('xls');

        /*
        die();
        
        return Response::json(['data' => $pedidos], 201);
        



         $data = array(
            array('data1', 'data2'),
            array('data3', 'data4')
        );

        Excel::create('Pedido', function($excel) use($data) {


                       

            $excel->sheet('Empresa1', function($sheet) use($data) {

                $sheet->fromArray($data);

            });

            $excel->sheet('Empresa 2', function($sheet) use($data) {

                $sheet->fromArray($data);

            });

        })->export('xls');*/
         
     }
}

?>