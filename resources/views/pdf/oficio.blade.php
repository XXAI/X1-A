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
	@if($acta->estatus < 3)
	<div id="watermark">SIN VALIDEZ</div>
	@endif
	<p class="texto-izquierda">
		<strong>
		SECRETARÍA DE SALUD<br>
		INSTITUTO DE SALUD<br>
		DIRECCIÓN DE ATENCIÓN MÉDICA
		</strong>
	</p>
	<p class="texto-derecha">
		<strong>
		@if($acta->empresa_clave != 'disur')
			Oficio No. DAM/SAH/CAMM/{{str_pad($acta->num_oficio,4,'0',STR_PAD_LEFT)}}/2016<br>
		@endif
		ASUNTO: SE SOLICITA COMPRA DE MEDICAMENTOS URGENTE.
		</strong><br><br>
		<span class="cursiva">
		@if($acta->empresa_clave != 'disur')
			Tuxtla Gutiérrez, Chiapas; a {{$acta->fecha_solicitud[2]}} de {{$acta->fecha_solicitud[1]}} de 2016.
		@endif
		</span>
	</p>
	<p class="texto-izquierda">
		<strong>
		{{$configuracion->director_administracion_finanzas}}<br>
		DIRECTOR DE ADMINISTRACIÓN Y FINANZAS <br>
		EDIFICIO.
		</strong>
	</p>
	<p class="texto-derecha">
		<strong>
		AT´N.  {{$configuracion->subdirector_recursos_materiales}}<br>
		SUBDIRECTOR DE RECURSOS MATERIALES<br>
		Y SERVICIOS GENERALES.
		</strong>
	</p>
	<p class="texto-justificado">
		Por medio del presente, solicitó a usted su valiosa intervención, para que se realice la compra inmediata y urgente del listado de medicamentos y material de curación para solventar las condiciones de desabasto existentes en {{$unidad->nombre}}, toda vez que derivado de notificación del desabasto mediante Acta Circunstanciada No. {{$acta->folio}} y como resultado de la verificación y comprobación por parte del personal de la comisión de abasto de medicamentos dependiente de esta Dirección, esta nos informan que del desabasto de medicamentos, material de curación e insumos médicos considerados en los pedidos Nos. {{$acta->requisiciones}};lo que implica un riesgo continuo el no contar con los insumos necesarios para la atención de usuarios de los servicios de atención hospitalaria y consulta externa.
	</p>
	<p class="texto-justificado">
 		No omito hacer mención a usted, que la presente solicitud se da con fundamento en el Contrato Abierto de Prestación de Servicios correspondiente a los pedidos con números {{$acta->requisiciones}}, celebrado entre este Instituto y la Empresa {{$empresa->nombre}}., en su Cláusula SEGUNDA, fracción VIII, párrafos primero y segundo, que a la letra dice:
	</p>
	<p class="cursiva">
		<strong>…“SEGUNDA. "EL PROVEEDOR"</strong> se obliga a lo siguiente:…
	</p>
	<p class="texto-justificado cursiva">
		…<strong>VIII. “EL PROVEEDOR”</strong> deberá mantener en existencia las cantidades necesarias de medicamentos y material de curación en cada módulo de distribución para hacer frente cualquier eventualidad o emergencia. Si por alguna razón imputable a <strong>“EL PROVEEDOR”</strong> llegara a existir faltante o desabasto de alguna clave de medicamentos o material de curación, para mantener la operatividad de las Unidades Médicas y no poner en riesgo la salud o incluso la vida misma de los usuarios de los servicios de salud brindados por <strong>“EL INSTITUTO”, “EL PROVEEDOR”</strong> se compromete a surtir en un periodo máximo de <strong>24 horas</strong> dichas claves; en caso de que terminado este plazo continuará el desabasto de medicamento o material de curación, entorpeciendo este acto el fin de privilegiar las acciones y medidas preventivas destinadas a evitar o mitigar el impacto negativo que tendría este hecho en la población, <strong>“EL INSTITUTO”</strong> podrá efectuar la compra inmediata de los medicamentos y material de curación en el mercado local…
	</p>
	<p class="texto-justificado cursiva">
 		...La compra de los medicamentos y material de curación que <strong>“EL INSTITUTO”</strong> adquiera con motivo del desabasto de alguna de las claves será realizada por la Subdirección de Recursos Materiales, a solicitud expresa de la Dirección de Atención Médica…”
	</p>
	<p class="texto-justificado">
		Sírvase a encontrar anexo al presente para su atención URGENTE, formato de Requisición No. {{$acta->numeros}}
	</p>
	<p class="texto-justificado">
		Sin más por el momento, reciba un cordial saludo.
	</p>
	<p class="texto-izquierda">
		<strong>A T E N T A M E N T E</strong>
	</p>
	<p class="texto-izquierda">
		<strong>
			{{$configuracion->director_atencion_medica}}<br>
			DIRECTORA DE ATENCIÓN MÉDICA
		</strong>
	</p>
	<p class="texto-izquierda">
		<strong>COPIAS PARA</strong>
	</p>
	<p class="texto-izquierda">
		Dr. Francisco Ortega Farrera.- Secretario de Salud y Director General del Instituto.- Para su Superior conocimiento.-
	</p>
	<p class="texto-izquierda">
		Archivo/Minutario
	</p>
</body>
</html>