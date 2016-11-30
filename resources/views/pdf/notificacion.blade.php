<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
		@page {
            margin-top: 10.3em;
            margin-left: 5.6em;
            margin-right: 6.6em;
            margin-bottom: 7.3em;
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
        
        .misma-linea{
        	display: inline-block;
        }
		.cuerpo{
			font-size: 8pt;
			font-family: arial, sans-serif;
		}
		p{
			/*font-size: 11pt;*/
			font-family: arial, sans-serif;
		}
		span.firma{
			/*text-decoration: underline;*/
			border-bottom: 1px solid black;
			padding-left: 50px;
			padding-right: 50px;
		}
		.texto{
			font-family: arial, sans-serif;
			/*font-size: 10pt;*/
		}
		.negrita{
			font-weight: bold;
		}
		.cursiva{
			font-style: italic;
		}
		.texto-medio{
			vertical-align: middle;
		}
		.texto-centro{
			text-align: center;
		}
		.texto-derecha{
			text-align: right !important;
		}
		.texto-izquierda{
			text-align: left;
		}
		.texto-justificado{
			text-align: justify;
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
		</table>
		<br><br><br><br><br>
		<div class="texto-centro cursiva">“2016, Año de Don Ángel Albino Corzo”</div>	
	</div>
	@if($acta->estatus < 4)
	<div id="watermark">SIN VALIDEZ</div>
	@endif
	<!-- {{$pagina = 0}} -->
@foreach($proveedores as $proveedor)
	
	@if($pagina > 0)
	<div style="page-break-after:always;"></div>
	@endif
	<!-- {{$pagina++}} -->
	<p class="texto-izquierda">
		<strong>
		SECRETARÍA DE SALUD<br>
		INSTITUTO DE SALUD<br>
		DIRECCIÓN DE ADMINISTRACIÓN Y FINANZAS<br>
		SUBDIRECCIÓN DE RECURSOS MATERIALES Y SERVICIOS GENERALES<br>
		DEPARTAMENTO DE RECURSOS MATERIALES
		</strong>
	</p>
	<p class="texto-derecha">
		<strong>
		@if($acta->empresa_clave != 'disur')
		Oficio No. DAF/SRMySG/DRM/{{$proveedor['num_oficio']}}/2016<br>
		@endif
		ASUNTO:</strong> Notificación de Adjudicación.
		<br><br>
		@if($proveedor['id'] != 7 && $acta->empresa_clave != 'disur')
		<span class="cursiva">
			Tuxtla Gutiérrez, Chiapas; a {{$acta->fecha_pedido[2]}} de {{$acta->fecha_pedido[1]}} de 2016.
		</span>
		@endif
	</p>
	<p class="texto-izquierda">
		<strong>
		{{$proveedor['nombre']}}<br>
		{{$proveedor['direccion']}},<br>
		{{$proveedor['ciudad']}}<br>
		TEL: {{$proveedor['telefono']}}
		</strong>
	</p>
	<p class="texto-justificado">
		En atención a la Solicitud enviada por la Dirección de Atención Medica me cumple informarle que se le fue adjudicado los insumos, correspondiente a las partidas <strong>{{$proveedor['partidas']}}</strong>, los cuales se encuentran en las requisiciones números {{$proveedor['requisiciones']}} anexas al presente, respetando las siguientes condiciones comerciales:
	</p>
	<p class="texto-justificado">
		<strong>LUGAR DE ENTREGA:</strong> {{$acta->lugar_entrega}}
	</p>
	<p class="texto-justificado">
		<strong>TIEMPO DE ENTREGA:</strong> Deberá surtir los insumos en un periodo no mayor a 48 horas posteriores a su notificación.
	</p>
	<p class="texto-justificado">
		<strong>CONDICIONES DE PAGO:</strong> 20 días naturales contados a partir de la recepción de la factura original, debidamente requisitada y previa validación de la unidad aplicativa a entera satisfacción de las mismas. Las facturas deberán presentarse a la Dirección de Atención Médica, mismas que enviaran a la Subdirección de Recursos Materiales y Servicios Generales para realizar el trámite correspondiente para este fin, conforme a la fuente de Financiamiento correspondiente.
	</p>

	<p class="texto-justificado">
		<strong>ANEXOS QUE DEBERÁ PRESENTAR:</strong>
	</p>

	<p class="texto-justificado">
		Carta original en papel membretado con nombre y firma autógrafa de su representante legal, en la que bajo protesta de decir verdad manifieste Que el tiempo mínimo de garantía de los bienes a entregar será de 4 meses, contados a partir de la entrega asumiendo el compromiso de cambiarlos en caso de defectos de fabricación, diferencias o vicios ocultos, en un plazo no mayor de 24 horas contados a partir de la notificación. Sin costo para el Instituto.
	</p>
	<p class="texto-justificado">
 		Es importante mencionar que los precios no deberán ser mayores a los de referencia, los cuales ya fueron hechos de su conocimiento con anterioridad.
	</p>
	<p class="texto-justificado">
		Sírvase a encontrar anexo al presente para su atención URGENTE, formato de Requisición No. {{$proveedor['requisiciones']}}
	</p>
	<p class="texto-justificado">
		Sin más por el momento quedo de usted. 
	</p>
	<p class="texto-izquierda">
		<strong>ATENTAMENTE</strong>
	</p>
	<p class="texto-izquierda">
		<strong>
			{{$configuracion->subdirector_recursos_materiales}}
		</strong><br>
			SUBDIRECTOR DE RECURSOS MATERIALES<br>
			Y SERVICIOS GENERALES.
	</p>
	<p class="texto-izquierda">
		<strong>Copias para:</strong>
	</p>
	<p class="texto-izquierda">
		Dr. Francisco Ortega Farrera.- Director General del Instituto de Salud y Secretario de Salud.- Para su superior conocimiento.- Edificio. 
	</p>
	<p class="texto-izquierda">
		Expediente / Archivo.
	</p>
@endforeach
</body>
</html>