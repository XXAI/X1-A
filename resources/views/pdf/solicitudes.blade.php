<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
		@page {
            margin-top: 7.3em;
            margin-left: 5.6em;
            margin-right: 6.6em;
            margin-bottom: 1.3em;
        }
        #watermark {
			position: fixed;
			top: 15%;
			left: 105px;
			transform: rotate(45deg);
			transform-origin: 50% 50%;
			opacity: .5;
			font-size: 120px;
			color: #DEDEDE;
			width: 480px;
			text-align: center;
		}
        table{
        	width:100%;
        	border-collapse: collapse;
        }
        
        .misma-linea{
        	display: inline-block;
        }
		.cuerpo{
			font-size: 10pt;
			font-family: arial, sans-serif;
		}
		.titulo2{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 9pt;
		}
		.titulo3{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 10pt;
		}
		.texto{
			font-family: arial, sans-serif;
			font-size: 12pt;
		}
		.negrita{
			font-weight: bold;
		}
		.linea-firma{
			border-bottom: 1 solid #000000;
		}
		.texto-medio{
			vertical-align: middle;
		}
		.texto-fondo{
			vertical-align: bottom !important;
		}
		.texto-centro{
			text-align: center;
		}
		.texto-derecha{
			text-align: right !important;
		}
		.texto-izquierda{
			text-align: left !important;
		}
		.texto-justificado{
			text-align: justify !important;
		}
		.encabezado-tabla{
			font-family: arial, sans-serif;
			font-size: 5pt;
			text-align: center;
			vertical-align: middle;
		}
		.tabla-datos{
			width: 100%;
		}
		.tabla-datos td,
		.tabla-datos th{
			border: thin solid #000000;
			border-collapse: collapse;
			padding:1;
		}
		.subtitulo-tabla{
			font-weight: bold;
			background-color: #DDDDDD;
		}
		.subsubtitulo-tabla{
			font-weight: bold;
			background-color: #EFEFEF;
		}
		.imagen{
			vertical-align: top;
		}

		.imagen.izquierda{
			text-align: left;
		}
		.imagen.derecha{
			text-align: right;
		}
		.imagen.centro{
			text-align: center;
		}
		.sin-bordes{
			border: none;
			border-collapse: collapse;
		}
		.header,.footer {
		    width: 100%;
		    text-align: center;
		    position: fixed;
		}
		.header {
		    top: -8.5em;
		}
		.footer {
		    bottom: 0px;
		}
		.pagenum:before {
		    content: counter(page);
		}
	</style>
</head>
<body class="cuerpo">
	<div class="header">
		<table>
			<tr>
				<td class="imagen izquierda">
					<img src="{{ public_path().'/img/LogoFederal.png' }}" height="45">
				</td>
				<td class="imagen centro">
					<img src="{{ public_path().'/img/MxSnTrabInf.jpg' }}" height="45">
				</td>
				<td class="imagen centro">
					<img src="{{ public_path().'/img/EscudoGobiernoChiapas.png' }}" height="45">
				</td>
				<td class="imagen derecha">
					<img src="{{ public_path().'/img/LogoInstitucional.png' }}" height="45">
				</td>
			</tr>
			<tr><td colspan="4" class="titulo2" align="center">INSTITUTO DE SALUD</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">DIRECCIÓN DE ATENCIÓN MÉDICA</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">COORDINACIÓN DE ABASTO</td></tr>
		</table>
	</div>
	@if($acta->estatus < 3)
	<div id="watermark">SIN VALIDEZ</div>
	@endif
@foreach($acta->requisiciones as $index => $requisicion)
	@if($index > 0)
	<div style="page-break-after:always;"></div>
	@endif
	<table width="100%">
		<thead>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-izquierda" width="20%">DIRECCIÓN RESPONSABLE</th>
				<th class="encabezado-tabla texto-izquierda" width="80%">DIRECCIÓN DE ATENCIÓN MÉDICA</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-izquierda" width="20%">ÁREA SOLICITANTE</th>
				<th class="encabezado-tabla texto-izquierda" width="80%">COORDINACIÓN DE ABASTO</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-izquierda" colspan="2" width="20%">CONCEPTO Y PARTIDA PRESUPUESTAL: {{$empresa['partidas'][$requisicion->pedido]}}</th>
			</tr>
		</thead>
	</table>
	<table width="100%">
		<thead>
			<tr class="tabla-datos">
				<th colspan="6" class="encabezado-tabla" align="center">REQUISICION DE {{($requisicion->tipo_requisicion == 1)?'MEDICAMENTOS CAUSES':(($requisicion->tipo_requisicion == 2)?'MEDICAMENTOS NO CAUSES':'MATERIAL DE CURACIÓN')}} </th>
			</tr>
			<tr class="tabla-datos">
				<th rowspan="2" width="20%" class="encabezado-tabla">REQUISICIÓN DE COMPRA</th>
				<th rowspan="2" width="20%" class="encabezado-tabla">UNIDAD MÉDICA EN DESABASTO</th>
				<th colspan="4" class="encabezado-tabla">DATOS</th>
			</tr>
			<tr class="tabla-datos">
				<th width="10%" class="encabezado-tabla">PEDIDO</th>
				<th width="7%" class="encabezado-tabla">LOTES A <br>ADJUDICAR</th>
				<th width="8%" class="encabezado-tabla">EMPRESA <br>ADJUDICADA EN <br>LICITACIÓN</th>
				<th width="35%" class="encabezado-tabla">DIAS DE SURTIMIENTO</th>
			</tr>
			<tr class="tabla-datos">
				<td class="encabezado-tabla">No. {{$requisicion->numero}}</td>
				<td class="encabezado-tabla">{{$unidad->nombre}}</td>
				<td class="encabezado-tabla">{{$requisicion->pedido}}</td>
				<td class="encabezado-tabla">{{$requisicion->lotes}}</td>
				<td class="encabezado-tabla">{{$empresa['nombre']}}</td>
				<td class="encabezado-tabla">{{$requisicion->dias_surtimiento}}</td>
			</tr>
		</thead>
	</table>
	<table width="100%">
		<thead>
			<tr class="tabla-datos">
				<th class="encabezado-tabla" width="10%">No. DE LOTE</th>
				<th class="encabezado-tabla" width="10%">CLAVE</th>
				<th class="encabezado-tabla" width="30%">DESCRIPCIÓN DEL INSUMO</th>
				<th class="encabezado-tabla" width="15%">CANTIDAD</th>
				<th class="encabezado-tabla" width="15%">UNIDAD DE MEDIDA</th>
				<th class="encabezado-tabla" width="10%">PRECIO <br>UNITARIO</th>
				<th class="encabezado-tabla" width="10%">TOTAL</th>
			</tr>
		</thead>
		<tbody>
		@foreach($requisicion->insumos as $indice => $insumo)
			<tr class="tabla-datos" style="page-break-inside:avoid;">
				<td class="encabezado-tabla">{{$insumo->lote}}</td>
				<td class="encabezado-tabla">{{$insumo->clave}}</td>
				<td class="encabezado-tabla"><small>{{$insumo->descripcion}}</small></td>
				<td class="encabezado-tabla">{{number_format($insumo->pivot->cantidad_aprovada)}}</td>
				<td class="encabezado-tabla">{{$insumo->unidad}}</td>
				<td class="encabezado-tabla">$ {{number_format($insumo->precio,2)}}</td>
				<td class="encabezado-tabla">$ {{number_format($insumo->pivot->total_aprovado,2)}}</td>
			</tr>
		@endforeach
		</tbody>
		<tfoot>
			<tr class="tabla-datos">
				<td colspan="4" rowspan="3" class="encabezado-tabla texto-medio texto-centro">CONDICIONES COMERCIALES</td>
				<th colspan="2" class="encabezado-tabla texto-derecha">SUBTOTAL</th>
				<td class="encabezado-tabla">$ {{number_format($requisicion->sub_total_validado,2)}}</td>
			</tr>
			<tr class="tabla-datos">
				<th colspan="2" class="encabezado-tabla texto-derecha">IVA</th>
				<td class="encabezado-tabla">{{($requisicion->tipo_requisicion==3)?'$ '.number_format($requisicion->iva_validado,2):'SIN IVA'}}</td>
			</tr>
			<tr class="tabla-datos">
				<th colspan="2" class="encabezado-tabla texto-derecha">GRAN TOTAL</th>
				<td class="encabezado-tabla">$ {{number_format($requisicion->gran_total_validado,2)}}</td>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-justificado" colspan="7">
					CONDICIONES DE PAGO: 20 días naturales contados a partir de la recepción de la factura original, debidamente requisitada y previa validación de la unidad aplicativa a entera satisfacción de las mismas. Las facturas deberán presentarse a la Dirección de Atención Médica, mismas que enviaran a la Subdirección de Recursos Materiales y Servicios Generales para realizar el trámite correspondiente para este fin, conforme a la fuente de Financiamiento correspondiente.
				</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-justificado" colspan="7">
					TIEMPO DE ENTREGA: Deberá surtir los insumos en un periodo no mayor a 48 horas posteriores a su notificación.
				</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-justificado" colspan="7">
					LUGAR DE ENTREGA: {{$acta->lugar_entrega}}
				</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-justificado" colspan="7">
					ANEXOS:<br>
					Carta original en papel membretado con nombre y firma autógrafa de su representante legal, en la que bajo protesta de decir verdad manifieste Que el tiempo mínimo de garantía de los bienes a entregar será de 4 meses, contados a partir de la entrega asumiendo el compromiso de cambiarlos en caso de defectos de fabricación, diferencias o vicios ocultos, en un plazo no mayor de 24 horas contados a partir de la notificación. Sin costo para el Instituto.
				</th>
			</tr>
		</tfoot>
	</table>
	<table width="100%">
		<tbody>
			<tr class="tabla-datos">
				<th class="encabezado-tabla">SOLICITA</th>
				<th class="encabezado-tabla">DIRECCIÓN O UNIDAD</th>
				<th width="50%" rowspan="3"></th>
			</tr>
			<tr class="tabla-datos">
				<td class="encabezado-tabla texto-fondo" height="30">{{$configuracion->coordinador_abasto}}</td>
				<td class="encabezado-tabla texto-fondo" height="30">{{$configuracion->director_atencion_medica}}</td>
			</tr>
			<tr class="tabla-datos">
				<td class="encabezado-tabla">COORDINADOR DE ABASTO</td>
				<td class="encabezado-tabla">DIRECTORA DE ATENCIÓN MÉDICA</td>
			</tr>
		</tbody>
	</table>
@endforeach
</body>
</html>