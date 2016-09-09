<?php
namespace App\Http\Traits;

use App\Models\Acta;
use App\Models\Requisicion;
use DB, Exception;

trait SyncTrait{
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
                $requisiciones_validadas = [];
                foreach ($acta_central->requisiciones as $requisicion) {
                    $tipo_requisicion = $requisicion->tipo_requisicion;

                    $requisiciones_validadas[$tipo_requisicion] = [
                        'estatus' => $requisicion->estatus,
                        'numero' => $requisicion->numero,
                        'sub_total_validado' => 0,
                        'lotes' => 0,
                        'insumos' => [],
                        'insumos_clues' => []
                    ];
                    if(count($requisicion->insumos)){
                        $insumos = [];
                        foreach ($requisicion->insumos as $req_insumo) {
                            $insumos[$req_insumo->llave] = [
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado,
                                'proveedor_id' => $req_insumo->pivot->proveedor_id
                            ];
                            $requisiciones_validadas[$tipo_requisicion]['sub_total_validado'] += $req_insumo->pivot->total_validado;
                        }
                        $requisiciones_validadas[$tipo_requisicion]['insumos'] = $insumos;
                        $requisiciones_validadas[$tipo_requisicion]['lotes'] = count($insumos);
                    }

                    if(count($requisicion->insumosClues)){
                        $insumos = [];
                        foreach ($requisicion->insumosClues as $req_insumo) {
                            $insumos[$req_insumo->llave .'.'.$req_insumo->pivot->clues] = [
                                'clues' => $req_insumo->pivot->clues,
                                'cantidad_validada' => $req_insumo->pivot->cantidad_validada,
                                'total_validado' => $req_insumo->pivot->total_validado
                            ];
                        }
                        $requisiciones_validadas[$tipo_requisicion]['insumos_clues'] = $insumos;
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
            return ['estatus'=>false,'message'=>$e->getMessage().' line:'.$e->getLine()];
            //return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}