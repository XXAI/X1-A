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
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, DateTime, Exception;

class ClonarActasController extends Controller
{
    public function clonar($id){
        try{
            $parametros = Input::all();

            $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->find($id);

            if(isset($parametros['clues'])){
                $clues_nueva = $parametros['clues'];
                $configuracion = DB::select('select * from samm_unidades.configuracion where clues = ?',[$clues_nueva]);
                
                if(count($configuracion) == 0){
                    return Response::json(['error' => 'datos de configuración no encontrados'], 500);
                }
            }else{
                $clues_nueva = $acta->clues;
            }

            $max_requisicion = 0;
            $max_acta = Acta::where('folio','like',$clues_nueva.'/%'.date('Y'))->max('numero');

            DB::beginTransaction();

            $nueva_acta = new Acta();

            $nueva_acta->folio = $clues_nueva . '/'.($max_acta+1).'/' . date('Y');
            $nueva_acta->numero = $max_acta+1;

            $nueva_acta->clues                         = $clues_nueva;
            $nueva_acta->fecha                         = $acta->fecha;
            $nueva_acta->fecha_solicitud               = $acta->fecha_solicitud;
            $nueva_acta->fecha_pedido                  = $acta->fecha_pedido;
            $nueva_acta->fecha_importacion             = $acta->fecha_importacion;
            $nueva_acta->fecha_termino                 = $acta->fecha_termino;
            $nueva_acta->fecha_validacion              = $acta->fecha_validacion;
            $nueva_acta->hora_inicio                   = $acta->hora_inicio;
            $nueva_acta->hora_termino                  = $acta->hora_termino;
            $nueva_acta->estatus                       = $acta->estatus;
            $nueva_acta->estatus_sincronizacion        = 1;
            $nueva_acta->updated_at                    = $acta->updated_at;

            if($clues_nueva != $acta->clues){
                $nueva_acta->ciudad                        = $configuracion[0]->localidad;
                $nueva_acta->lugar_reunion                 = $configuracion[0]->clues_nombre;
                $nueva_acta->lugar_entrega                 = $configuracion[0]->lugar_entrega;
                $nueva_acta->director_unidad               = $configuracion[0]->director_unidad;
                $nueva_acta->administrador                 = $configuracion[0]->administrador;
                $nueva_acta->encargado_almacen             = $configuracion[0]->encargado_almacen;
                $nueva_acta->coordinador_comision_abasto   = $configuracion[0]->coordinador_comision_abasto;
                $nueva_acta->empresa_clave                 = $configuracion[0]->empresa_clave;
            }else{
                $nueva_acta->ciudad                        = $acta->ciudad;
                $nueva_acta->lugar_reunion                 = $acta->lugar_reunion;
                $nueva_acta->lugar_entrega                 = $acta->lugar_entrega;
                $nueva_acta->director_unidad               = $acta->director_unidad;
                $nueva_acta->administrador                 = $acta->administrador;
                $nueva_acta->encargado_almacen             = $acta->encargado_almacen;
                $nueva_acta->coordinador_comision_abasto   = $acta->coordinador_comision_abasto;
                $nueva_acta->empresa_clave                 = $acta->empresa_clave;
            }

            if($acta->estatus >= 3){
                $max_oficio = Acta::max('num_oficio');
                $nueva_acta->num_oficio = $max_oficio+1;

                //Se obtiene el numero de requisición máximo
                $actas = Acta::where('clues',$clues_nueva)->lists('id');
                $max_requisicion = Requisicion::whereIn('acta_id',$actas)->max('numero');
                if(!$max_requisicion){
                    $max_requisicion = 0;
                }

                if($acta->estatus == 4){
                    $max_oficio = Acta::max('num_oficio_pedido');
                    if(!$max_oficio){
                        $max_oficio = 0;
                    }
                    $nueva_acta->num_oficio_pedido = ($max_oficio + 1);
                }
            }

            if($nueva_acta->save()){
                $lista_proveedores = [];

                foreach ($acta->requisiciones as $requisicion) {
                    $nueva_requisicion = new Requisicion();

                    if($nueva_acta->estatus >= 3){
                        $max_requisicion++;
                        $nueva_requisicion->numero            = $max_requisicion;
                    }

                    $nueva_requisicion->estatus               = $requisicion->estatus;
                    $nueva_requisicion->pedido                = $requisicion->pedido;
                    $nueva_requisicion->lotes                 = $requisicion->lotes;
                    $nueva_requisicion->tipo_requisicion      = $requisicion->tipo_requisicion;
                    $nueva_requisicion->dias_surtimiento      = $requisicion->dias_surtimiento;
                    $nueva_requisicion->sub_total             = $requisicion->sub_total;
                    $nueva_requisicion->gran_total            = $requisicion->gran_total;
                    $nueva_requisicion->iva                   = $requisicion->iva;
                    $nueva_requisicion->sub_total_validado    = $requisicion->sub_total_validado;
                    $nueva_requisicion->gran_total_validado   = $requisicion->gran_total_validado;
                    $nueva_requisicion->iva_validado          = $requisicion->iva_validado;
                    $nueva_requisicion->updated_at            = $requisicion->updated_at;

                    $nueva_acta->requisiciones()->save($nueva_requisicion);

                    $insumos = [];
                    foreach ($requisicion->insumos as $req_insumo) {
                        $proveedor_id = $req_insumo->pivot->proveedor_id;

                        /*if($requisicion->tipo_requisicion == 4){
                            if(isset($parametros['proveedor_controlados_id'])){
                                $proveedor_id = $parametros['proveedor_controlados_id'];
                            }
                        }else*/
                        if(isset($parametros['proveedor_id'])){
                            $proveedor_id = $parametros['proveedor_id'];
                        }

                        if($proveedor_id){
                            if(!isset($lista_proveedores[$proveedor_id])){
                                $lista_proveedores[$proveedor_id] = true;
                            }
                        }

                        $insumos[] = [
                            'insumo_id'         => $req_insumo->id,
                            'cantidad'          => $req_insumo->pivot->cantidad,
                            'total'             => $req_insumo->pivot->total,
                            'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                            'total_validado'    => $req_insumo->pivot->total_validado,
                            'proveedor_id'      => $proveedor_id
                        ];
                    }
                    $nueva_requisicion->insumos()->sync($insumos);

                    $insumos = [];
                    foreach ($requisicion->insumosClues as $req_insumo) {
                        $insumos[] = [
                            'insumo_id'         => $req_insumo->id,
                            'clues'             => $req_insumo->pivot->clues,
                            'cantidad'          => $req_insumo->pivot->cantidad,
                            'total'             => $req_insumo->pivot->total,
                            'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                            'total_validado'    => $req_insumo->pivot->total_validado
                        ];
                    }
                    $nueva_requisicion->insumosClues()->sync($insumos);
                }

                if(count($lista_proveedores)){
                    if($nueva_acta->estatus == 4){
                        $oficio_consecutivo = $nueva_acta->num_oficio_pedido;
                    }else{
                        $oficio_consecutivo = 0;
                    }
                    $proveedores_sync = [];
                    foreach ($lista_proveedores as $proveedor_id => $valor) {
                        $proveedores_sync[] =[
                            'acta_id' => $nueva_acta->id,
                            'proveedor_id' => $proveedor_id,
                            'num_oficio' => $oficio_consecutivo
                        ];
                        if($nueva_acta->estatus == 4){
                            $oficio_consecutivo++;
                        }
                    }
                    $nueva_acta->proveedores()->sync($proveedores_sync);

                    if($nueva_acta->estatus == 4){
                        $nueva_acta->num_oficio_pedido = ($oficio_consecutivo-1);
                        $nueva_acta->save();
                    }
                }
            }else{
                throw new Exception("Acta no creada", 1);
            }

            DB::commit();

            return Response::json([ 'data' => $acta], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
