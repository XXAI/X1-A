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
			color: #CCCCCC;
			width: 480px;
			text-align: center;
		}
        table{
        	width:100%;
        	border-collapse: collapse;
        }
        .fondo-titulo{
        	background-color: #EAF1DD;
        }
        .misma-linea{
        	display: inline-block;
        }
		.cuerpo{
			font-size: 6pt;
			font-family: arial, sans-serif;
		}
		.pequenio{
			font-size: 7pt !important;
		}
		.titulo2{
			font-weight: bold;
			font-family: arial, sans-serif;
			/*font-size: 9pt;*/
		}
		.titulo3{
			font-weight: bold;
			font-family: arial, sans-serif;
			/*font-size: 10pt;*/
		}
		.texto{
			font-family: arial, sans-serif;
			/*font-size: 12pt;*/
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
			/*font-size: 5pt;*/
			text-align: center;
			vertical-align: middle;
		}
		.linea-tabla{
			font-family: arial, sans-serif;
			/*font-size: 5pt;*/
			text-align: center;
			vertical-align: middle;
			border-bottom:none !important;
			border-top: none !important;
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
		    top: -12.5em;
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
			<tr><td colspan="4" class="titulo2" align="center">SECRETARÍA DE SALUD</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">INSTITUTO DE SALUD</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">DIRECCIÓN DE ADMINISTRACIÓN Y FINANZAS</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">SUBDIRECCION DE RECURSOS MATERIALES Y SERVICIOS GENERALES</td></tr>
		</table>
	</div>
	@if($estatus < 4)
	<div id="watermark">SIN VALIDEZ</div>
	@endif

	<!-- {{$pagina = 0}} -->

	@foreach($pedidos as $proveedores)
	@foreach($proveedores as $proveedor)
	@foreach($proveedor as $index => $pedido)
	@if($pagina > 0)
	<div style="page-break-after:always;"></div>
	@endif
	<!-- {{$pagina++}} -->
	<table width="100%">
		<tbody>
			<tr class="tabla-datos">
				<th colspan="5" class="encabezado-tabla fondo-titulo">PEDIDO EMERGENTE DE ABASTOS A UNIDADES MEDICAS EN RELACION AL CONTRATO ABIERTO DE PRESTACION DE SERVICIO.</th>
				<th rowspan="2" class="encabezado-tabla fondo-titulo"> No. DE OFICIO DE SOLICITUD DEL ÁREA MÉDICA</th>
				<th rowspan="2" class="encabezado-tabla">{{$pedido['oficio']}}</th>
			</tr>
			<tr class="tabla-datos">
				<th colspan="5" class="encabezado-tabla texto-justificado" rowspan="4">
				<small>
					<p>
					FUNDAMENTO LEGAL: CLAUSULA SEGUNDA NUMERALES VIII párrafos primero y segundo Y IX ultimo parrafo del contrato contraido con la empresa {{$empresa->nombre}} que a la letra dice:…
					</p>
					<p>
					“SEGUNDA. &quot;EL PROVEEDOR&quot; se obliga a lo siguiente:…”
					</p>
					<p>
					…”VIII. “EL PROVEEDOR” deberá mantener en existencia las cantidades necesarias de medicamentos y material de curación en cada modulo de distribución. Si por alguna razón imputable a “EL PROVEEDOR” llegara a existir faltante de alguna clave de medicamentos o material de curación, para mantener la operatividad de las Unidades Medicas y no poner en riesgo la salud o incluso la vida misma de los usuarios de los servicios de salud brindados por “EL INSTITUTO”, “EL PROVEEDOR” se compromete a surtir en un periodo máximo de 24 horas dichas claves; en caso de que terminado este plazo continuara el desabasto de medicamento o material de curación, entorpeciendo este acto el fin de privilegiar las acciones y medidas preventivas destinadas a evitar o mitigar el impacto negativo que tendría este hecho en la población, “EL INSTITUTO” podrá efectuar la compra inmediata de los medicamentos y material de curación en el mercado local.....
					</p>
					<p>
					...La compra de los medicamentos y material de curación que “EL INSTITUTO” adquiera con motivo del desabasto de alguna de las claves será realizada por la Subdirección de Recursos Materiales, a solicitud expresa de la Dirección de Atención Médica…”
					</p>
					<p>
					...&quot;IX. ...
					</p>
					<p>
					...El importe que se genere de los pagos que “EL INSTITUTO” realizará a los proveedores locales que cubran el desabasto, estará incluido y con cargo al monto total máximo establecido en la CLAUSULA TERCERA del presente contrato.”…
					</p>
				</small>
				</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">PARTIDA PRESUPUESTAL:</th>
				<th class="encabezado-tabla">{{$empresa->partida_presupuestal}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">EMPRESA ADJUDICADA EN LICITACIÓN</th>
				<th class="encabezado-tabla">{{$empresa->nombre}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">NÚMERO DE PEDIDO ADJUDICADO EN LICITACIÓN</th>
				<th class="encabezado-tabla">{{$pedido['pedido']}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">PROVEEDOR ADJUDICADO</th>
				<th colspan="2" class="encabezado-tabla">{{$pedido['proveedor']}}</th>
				<th class="encabezado-tabla fondo-titulo">No. DE REQUISICIÓN</th>
				<th colspan="3" class="encabezado-tabla">{{$pedido['no_requisicion']}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">LUGAR DE ENTREGA</th>
				<th colspan="2" class="encabezado-tabla">{{$pedido['lugar_entrega']}}</th>
				<th class="encabezado-tabla fondo-titulo">CONDICIONES DE PAGO</th>
				<th colspan="3" class="encabezado-tabla"> 20 días naturales contados a partir de la recepción de la factura original, debidamente requisitada y previa validación de la unidad aplicativa a entera satisfacción de las mismas. Las facturas deberán presentarse a la Dirección de Atención Médica, mismas que enviaran a la Subdirección de Recursos Materiales y Servicios Generales para realizar el trámite correspondiente para este fin, conforme a la fuente de Financiamiento correspondiente.</th>
			</tr>
		</tbody>
	<!--/table>
	<table width="100%"-->
		<thead>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo">No. DE LOTE</th>
				<th class="encabezado-tabla fondo-titulo">CLAVE</th>
				<th class="encabezado-tabla fondo-titulo">DESCRIPCIÓN DE LOS INSUMOS</th>
				<th class="encabezado-tabla fondo-titulo">CANTIDAD</th>
				<th class="encabezado-tabla fondo-titulo">UNIDAD DE MEDIDA</th>
				<th class="encabezado-tabla fondo-titulo">PRECIO UNITARIO</th>
				<th class="encabezado-tabla fondo-titulo">PRECIO TOTAL</th>
			</tr>
		</thead>
		<tbody>

		@foreach($pedido['insumos'] as $insumo)
			<tr class="tabla-datos">
				<td class="linea-tabla texto-centro">{{$insumo['lote']}}</td>
				<td class="linea-tabla texto-centro">{{$insumo['clave']}}</td>
				<td class="linea-tabla texto-centro">
					<div style="page-break-inside:avoid;"><small>{{$insumo['descripcion']}}</small></div>
				</td>
				<td class="linea-tabla texto-centro">{{number_format($insumo['pivot']['cantidad_aprovada'])}}</td>
				<td class="linea-tabla texto-centro">{{$insumo['unidad']}}</td>
				<td class="linea-tabla texto-centro">$ {{number_format($insumo['precio'],2)}}</td>
				<td class="linea-tabla texto-centro">$ {{number_format($insumo['pivot']['total_aprovado'],2)}}</td>
			</tr>
		@endforeach

		<!--/tbody>
	</table>
	<table width="100%">
		<tbody-->
			<tr class="tabla-datos">
				<th class="encabezado-tabla" rowspan="3" colspan="2">
					<img src="{{ public_path().'/img/Marca.png' }}" width="125">
				</th>
				<th class="encabezado-tabla texto-justificado texto-fondo" rowspan="3" colspan="3">
					Facturar 2016 a nombre del Instituto de Salud. Unidad Administrativa Edif. C, Maya Tuxtla Gutiérrez, Chiapas, 29010 R.F.C. ISA-961203- QN5
				</th>
				<th class="encabezado-tabla fondo-titulo" >SUBTOTAL</th>
				<th class="encabezado-tabla" >$ {{number_format($pedido['sub_total'],2)}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo" >I.V.A.</th>
				<th class="encabezado-tabla">$ {{number_format($pedido['iva'],2)}}</th>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla fondo-titulo" >T O T A L</th>
				<th class="encabezado-tabla" >$ {{number_format($pedido['gran_total'],2)}}</th>
			</tr>
			<tr class="tabla-datos">
				<th colspan="7" class="encabezado-tabla fondo-titulo texto-izquierda">
					IMPORTE TOTAL: ({{$pedido['total_letra']}} M.N.)
				</th>
			</tr>
			<tr class="tabla-datos">
				<th colspan="7" class="encabezado-tabla fondo-titulo texto-izquierda">
					FUENTE DE FINANCIAMIENTO: {{$pedido['fuente_financiamiento']}}
				</th>
			</tr>
			<tr class="tabla-datos">
				<th colspan="7" class="encabezado-tabla fondo-titulo texto-izquierda">
					TIEMPO DE ENTREGA: Deberá surtir los insumos en un periodo no mayor a 48 horas posteriores a su notificación
				</th>
			</tr>
		</tbody>
	</table>
	@endforeach
	@endforeach
	@endforeach
</body>
</html>