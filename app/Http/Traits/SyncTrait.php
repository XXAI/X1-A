<?php
namespace App\Http\Traits;

use App\Models\Acta;
use App\Models\Requisicion;
use DB, Exception, DateTime;

trait SyncTrait{
	public function actualizarUnidades($folio){
        try{
            $acta_central = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->where('folio',$folio)->first();


            if(($acta_central->estatus == 3 && $acta_central->estatus_sincronizacion == 2) || ($acta_central->estatus == 4 && $acta_central->estatus_sincronizacion == 3)){
                throw new Exception('El acta ya se encuentra sincronizada', 1);
            }

            $default = DB::getPdo(); // Default conn
            $secondary = DB::connection('mysql_sync')->getPdo();

            $stamp_sincronizado = new DateTime();

            DB::setPdo($secondary);
            DB::beginTransaction();

            //$conexion_remota = DB::connection('mysql_sync');
            //$conexion_remota->beginTransaction();

            $acta_unidad = new Acta();
            $acta_unidad = $acta_unidad->setConnection('mysql_sync');
            $acta_unidad = $acta_unidad->with('requisiciones.insumos','requisiciones.insumosClues')->where('folio',$folio)->first();

            if(!$acta_unidad){
                $acta_unidad = new Acta();
                $acta_unidad->folio                         = $acta_central->folio;
                $acta_unidad->ciudad                        = $acta_central->ciudad;
                $acta_unidad->fecha                         = $acta_central->fecha;
                $acta_unidad->hora_inicio                   = $acta_central->hora_inicio;
                $acta_unidad->hora_termino                  = $acta_central->hora_termino;
                $acta_unidad->lugar_reunion                 = $acta_central->lugar_reunion;
                $acta_unidad->lugar_entrega                 = $acta_central->lugar_entrega;
                $acta_unidad->empresa                       = $acta_central->empresa_clave;
                $acta_unidad->estatus                       = $acta_central->estatus;
                $acta_unidad->estatus_sincronizacion        = $acta_central->estatus_sincronizacion;
                $acta_unidad->director_unidad               = $acta_central->director_unidad;
                $acta_unidad->administrador                 = $acta_central->administrador;
                $acta_unidad->encargado_almacen             = $acta_central->encargado_almacen;
                $acta_unidad->coordinador_comision_abasto   = $acta_central->coordinador_comision_abasto;
                $acta_unidad->numero                        = $acta_central->numero;
                $acta_unidad->created_at                    = $acta_central->created_at;
                $acta_unidad->updated_at                    = $acta_central->updated_at;
            }

            $acta_unidad->fecha_validacion = $acta_central->fecha_validacion;
            $acta_unidad->estatus = $acta_central->estatus;
            if($acta_central->estatus == 4){
                $acta_unidad->sincronizado_termino = $stamp_sincronizado;
                if(!$acta_unidad->sincronizado_validacion){
                    $acta_unidad->sincronizado_validacion = $stamp_sincronizado;
                }
                $acta_unidad->estatus_sincronizacion = 3;
            }else{
                $acta_unidad->sincronizado_validacion = $stamp_sincronizado;
                $acta_unidad->estatus_sincronizacion = 2;
            }
            
            $insumos_nuevos_central = [];
            $insumos_clues_nuevos_central = [];
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
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'requisicion_id_unidad' => $req_insumo->pivot->requisicion_id_unidad
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
                                }else{ //Si el insumo no esta en los datos de oficina central se llenan a 0 y se guardan en central
                                    $nuevo_insumo['total_validado'] = 0;
                                    $nuevo_insumo['cantidad_validada'] = 0;
                                    if(!isset($insumos_nuevos_central[$requisicion->tipo_requisicion])){
                                        $insumos_nuevos_central[$requisicion->tipo_requisicion] = [];
                                    }
                                    $insumos_nuevos_central[$requisicion->tipo_requisicion][] = [
                                        'insumo_id' => $insumo->id,
                                        'cantidad' => $insumo->pivot->cantidad,
                                        'total' => $insumo->pivot->total,
                                        'cantidad_validada' => 0,
                                        'total_validado' => 0
                                    ];
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
                                    'total' => $insumo->pivot->total,
                                    'requisicion_id_unidad' => $insumo->pivot->requisicion_id_unidad
                                ];
                                if(isset($requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues])){
                                    $insumo_import = $requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues];
                                    $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                    $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                                }else{ //Si el insumo no esta en los datos de oficina central se llenan a 0 y se guardan en central
                                    $nuevo_insumo['total_validado'] = 0;
                                    $nuevo_insumo['cantidad_validada'] = 0;
                                    if(!isset($insumos_clues_nuevos_central[$requisicion->tipo_requisicion])){
                                        $insumos_clues_nuevos_central[$requisicion->tipo_requisicion] = [];
                                    }
                                    $insumos_clues_nuevos_central[$requisicion->tipo_requisicion][] = [
                                        'insumo_id' => $insumo->id,
                                        'clues' => $insumo->pivot->clues,
                                        'cantidad' => $insumo->pivot->cantidad,
                                        'total' => $insumo->pivot->total,
                                        'cantidad_validada' => 0,
                                        'total_validado' => 0
                                    ];
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

            DB::beginTransaction();

            if($acta_central->estatus == 4){
                $acta_central->sincronizado_termino = $stamp_sincronizado;
                if(!$acta_central->sincronizado_validacion){
                    $acta_central->sincronizado_validacion = $stamp_sincronizado;
                }
                $acta_central->estatus_sincronizacion = 3;
            }else{
                $acta_central->sincronizado_validacion = $stamp_sincronizado;
                $acta_central->estatus_sincronizacion = 2;
            }
            $acta_central->save();

            //Agregamos a central los insumos que no encontramos en central pero si en unidades
            foreach ($acta_central->requisiciones as $requisicion) {
                if(count($insumos_nuevos_central)){
                    if(isset($insumos_nuevos_central[$requisicion->tipo_requisicion])){
                        $insumos = $insumos_nuevos_central[$requisicion->tipo_requisicion];
                        foreach ($requisicion->insumos as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo->id,
                                'cantidad' => $req_insumo->pivot->cantidad,
                                'total' => $req_insumo->pivot->total,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'proveedor_id' => $req_insumo->pivot->proveedor_id
                            ];
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos);
                    }
                }
                if(count($insumos_clues_nuevos_central)){
                    if(isset($insumos_clues_nuevos_central[$requisicion->tipo_requisicion])){
                        $insumos = $insumos_clues_nuevos_central[$requisicion->tipo_requisicion];
                        foreach ($requisicion->insumosClues as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo->id,
                                'clues' => $req_insumo->pivot->clues,
                                'cantidad' => $req_insumo->pivot->cantidad,
                                'total' => $req_insumo->pivot->total,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'requisicion_id_unidad' => $req_insumo->pivot->requisicion_id_unidad
                            ];
                        }
                        $requisicion->insumosClues()->sync([]);
                        $requisicion->insumosClues()->sync($insumos);
                    }
                }
            }
            
            DB::commit();

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