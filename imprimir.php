<?php

require_once('lib/imprimir.class.php');

$app->group('/imprimir', function () use ($app, $db) {

	$app->get('/', function() use ($app, $db) {
		$almacen = $db->almacen;
		$app->response()->write( json_encode($almacen));
	});

	$app->get('/precuenta/:cajaId/:nroAtencion', function($cajaId, $nroAtencion) use ($app, $db) {

		$atencion = $db->atenciones->where("caja_id=? AND nroatencion=?", array($cajaId, $nroAtencion));

		$cia = null;
		$impresora = "LPT1"; //192.168.0.100
		$despedida = "";
		$caja = $db->caja->where("id", $cajaId);

		if($row = $caja->fetch()){
			$cia = $row->centrocosto->empresa;
			$despedida = $row->centrocosto["mensaje"];
			$impresora = $row['impresora_p'];
		}

		$hasCabecera = false;
		$cabecera = $db->ticket->where("modulo=? AND nombre=?", array("PRECUENTA", "CABECERA"));
		if($row=$cabecera->fetch()){
			$hasCabecera = $row['valor']=='S' ? true : false;
		}

		$objCajero = $db->usuario->where("id", $atencion->fetch()["cajero_id"])->fetch();
		$cajero = $objCajero["nombre"]." ".$objCajero["apellido"];

		$objMozo = $db->usuario->where("id", $atencion->fetch()["mozo_id"])->fetch();
		$mozo = $objMozo["nombre"]." ".$objMozo["apellido"];

		$imprimir = new Imprimir($impresora, $cia);

		$response = $imprimir->precuenta($atencion, array(
			"cabecera" => $hasCabecera, 
			"cajero" => $cajero, 
			"mozo" => $mozo,
			"despedida" => $despedida
		));

		$app->response()->write(json_encode($response));
	});

	$app->get('/comprobante/:id/:ticket', function($id, $ticket) use ($app, $db) {
		$ticket = $ticket=="true"?true:false;
		$cabecera = $db->venta->where("id", $id);
		$detalle = $db->venta_detalle->where("venta_id", $id);

		$cia = null;
		$impresora = "LPT1"; //192.168.0.100
		$despedida = "";

		$venta = $cabecera->fetch();
		$caja = $db->caja->where("id", $venta["caja_id"]);
		$tipo = $venta['tipo_documento_id'];
		$nroatencion = $venta['nroatencion'];
		$serie = $venta['serie'];
		$numero = $venta['numero'];
		$servicio = 0;
		$igv = $db->impuesto->where("nombre","IGV")->fetch()["valor"];
		if($row = $caja->fetch()){
			$cia = $row->centrocosto->empresa;
			$despedida = $row->centrocosto["mensaje"];
			$registradora = $row['seriecaja'];
			$autorizacion = $row['autorizacion'];
			$servicio = $row->centrocosto["servicio"];
			$impresora = $ticket?$row['impresora_p']:$tipo==Imprimir::FACTURA?$row['impresora_f']:$row['impresora_b'];
		}

		$objCajero = $db->usuario->where("id", $venta["cajero_id"])->fetch();
		$cajero = $objCajero["nombre"]." ".$objCajero["apellido"];

		$cliente = [];
		$cliente['cliente_id'] = $venta["cliente_id"];
		$objCliente = $db->cliente->where("id", $venta["cliente_id"]);
		if($row=$objCliente->fetch()){
			$cliente['nombre'] = $row['nombre'];
			$cliente['ruc'] = $row['ruc'];
			$dep = $db->ubigeo->where("co_departamento=? AND co_provincia=0 AND co_distrito=0", $row->ubigeo['co_departamento'])->fetch()['nombre'];
			$pro = $db->ubigeo->where("co_departamento=? AND co_provincia=? AND co_distrito=0", array($row->ubigeo['co_departamento'], $row->ubigeo['co_provincia']))->fetch()['nombre'];
			$cliente['direccion'] = $row['direccion'].'-'.$dep.'-'.$pro.'-'.$row->ubigeo['nombre'];
		}

		try{
			if($imprimir = new Imprimir($impresora, $cia)){
				$response = $imprimir->comprobante($cliente, $detalle, array(
					"ticket" => $ticket,
					"cajero" => $cajero,
					"despedida" => $despedida,
					"registradora" => null,
					"autorizacion" => null,
					"factura" => $tipo==Imprimir::FACTURA ? true : false,
					"registradora" => $registradora,
					"autorizacion" => $autorizacion,
					"serie" => $serie,
					"numero" => $numero,
					"igv" => $igv,
					"servicio" => $servicio,
					"nroatencion" => $nroatencion
				));
			}
		}catch(Exception $e){
			$response["data"]['success'] = false;
			$response["data"]['error'] = true;
			$response["data"]['message'] = Messages::ERR_PRINTING;
		}

		$app->response()->write(json_encode($response));
	});

	$app->get('/pedido/liberar/:id', function($id) use ($app, $db) {
		$cabecera = $db->liberado->where("id", $id);

		$impresora = "LPT1";
		$liberado = $cabecera->fetch();
		$caja = $db->caja->where("id", $liberado["caja_id"]);
		if($row = $caja->fetch()){
			$impresora = $row['impresora_p'];
		}

		$objCajero = $db->usuario->where("id", $liberado["cajero_id"])->fetch();
		$cajero = $objCajero["nombre"]." ".$objCajero["apellido"];

		$imprimir = new Imprimir($impresora);
		$response = $imprimir->liberar($liberado, array(
			"cajero" => $cajero,
			"total" => $liberado["total"]
		));

		$app->response()->write(json_encode($response));
	});

	$app->get('/pedido/:cajaId/:nroAtencion', function($cajaId, $nroAtencion) use ($app, $db) {
		$response = [];
		$response["success"] = true;
		$atencion = $db->atenciones->where("caja_id=? AND nroatencion=?", array($cajaId, $nroAtencion));
		$objMozo = $db->usuario->where("id", $atencion->fetch()["mozo_id"])->fetch();
		$mozo = $objMozo["nombre"]." ".$objMozo["apellido"];

		$destinos = $db->destino;
		foreach ($destinos as $row) {
			$atencion = $db->atenciones->where("caja_id=? AND nroatencion=? AND enviado='N' AND producto.destino_id=?", array($cajaId, $nroAtencion, $row["id"]));
			if($atencion->fetch()){
				try{
					if($imprimir = new Imprimir($row["destino"])){
						$response = $imprimir->pedido($atencion, $row["nombre"], array(
							"mozo" => $mozo
						));
						if($response["data"]['success']){
							foreach ($atencion as $row) {
								$row->update(array("enviado" => "S"));
							}
						}
					}
				}catch(Exception $e){
					$response["data"]['success'] = false;
					$response["data"]['error'] = true;
					$response["data"]['message'] = Messages::ERR_PRINTING;
				}
			}
		}

		$app->response()->write(json_encode($response));
	});

	$app->get('/pedido/:tipo/:atencionId/:cantidad', function($tipo, $atencionId, $cantidad) use ($app, $db) {
		$atencion = $db->atenciones->where("id", $atencionId);
		$row = $atencion->fetch();
		$row["cantidad"] = $cantidad;

		$objMozo = $db->usuario->where("id", $atencion["mozo_id"])->fetch();
		$mozo = $objMozo["nombre"]." ".$objMozo["apellido"];

		$destino = $row->producto->destino["destino"];
		$destinoNombre = $row->producto->destino["nombre"];
		$imprimir = new Imprimir($destino);
		$response = $imprimir->pedido($atencion, $destinoNombre, array(
			"mozo" => $mozo
		), $tipo);

		$app->response()->write(json_encode($response));
	});

	$app->get('/cierre/:cajaId(/:cajeroId)', function($cajaId, $cajeroId=0) use ($app, $db) {
		$response = [];
		$response["data"] = array('success' => false, "error" => true, "message" => 'No hay ventas que procesar');

		$atenciones = $db->atenciones->where("caja_id", $cajaId);
		if($atenciones->fetch() && $cajeroId==0){
			$response["data"]["message"] = Messages::WAR_Z;
		}else{
			$caja = $db->caja->where("id", $cajaId)->fetch();
			$dia = $caja["dia"];
			$ventas = $db->venta->where("caja_id=? AND dia=?", array($cajaId, $dia));
			if($cajeroId>0){
				$ventas->where("cajero_id", $cajeroId);
			}
			if(COUNT($ventas)>0){
				$igv = $db->impuesto->where("nombre","IGV")->fetch()["valor"];
				$servicio = $caja->centrocosto["servicio"];
				$cia = $caja->centrocosto->empresa;
				$impresora = $cajeroId>0 ? $caja['impresora_x'] : $caja['impresora_z'];
				$cajero = "";
				$objCajero = $db->usuario->where("id", $cajeroId);
				if($row=$objCajero->fetch()){
					$cajero = $row["nombre"]." ".$row["apellido"];
				}
				$comprobante = [];
				$objComprobante = $db->venta->select("DATE_FORMAT(fechahora, '%d-%m-%Y') AS fechahora")->where("caja_id=? AND dia=?", array($cajaId, $dia));
				if($cajeroId>0){
					$objComprobante->where("cajero_id", $cajeroId);
				}
				$firstDay = $objComprobante->fetch()["fechahora"];
				$lastDay = "";
				foreach ($objComprobante as $row) {
					$lastDay = $row["fechahora"];
				}
				$comprobante["firstDay"] = $firstDay;
				$comprobante["lastDay"] = $lastDay;
				foreach (array(Imprimir::FACTURA, Imprimir::BOLETA) as $tipo) {
					$objComprobante = $db->venta->select("serie, numero, base, igv, servicio, total")->where("caja_id=? AND dia=? AND tipo_documento_id=? AND anulado_id=0", array($cajaId, $dia, $tipo))->order("numero");
					if($cajeroId>0){
						$objComprobante->where("cajero_id", $cajeroId);
					}
					$count = count($objComprobante);
					$first = $objComprobante->fetch()["numero"];
					$serie = $objComprobante->fetch()["serie"];
					$first = Util::right($serie, 3, "0")."-".Util::right($first, 7, "0");
					$last = 0;
					$base = 0;
					$mIgv = 0;
					$mServicio = 0;
					$total = 0;
					foreach ($objComprobante as $row) {
						$last = $row["numero"];
						$base += $row["base"];
						$mIgv += $row["igv"];
						$mServicio += $row["servicio"];
						$total += $row["total"];
					}
					$last = Util::right($serie, 3, "0")."-".Util::right($last, 7, "0");
					$objComprobante = $db->venta->where("caja_id=? AND dia=? AND tipo_documento_id=? AND anulado_id>0", array($cajaId, $dia, $tipo));
					if($cajeroId>0){
						$objComprobante->where("cajero_id", $cajeroId);
					}
					$anulados = count($objComprobante);
					//$objComprobante = $db->venta->select("serie, numero")->where("caja_id=? AND dia=? AND tipo_documento_id=? AND anulado_id=0", array($cajaId, $dia, $tipo))->order("numero");
					$comprobante[$tipo] = array(
						"count" => $count,
						"first" => $first,
						"last" => $last,
						"anulados" => $anulados,
						"base" => $base,
						"igv" => $mIgv,
						"servicio" => $mServicio,
						"total" => $total
					);
				}
				$productos = $db->venta_detalle->select("producto_name, precio, SUM(cantidad) AS cantidad")->where("venta.caja_id=? AND venta.dia=? AND venta.anulado_id=0", array($cajaId, $dia))->group("producto_name, precio");
				if($cajeroId>0){
					$productos->where("venta.cajero_id", $cajeroId);
				}

				$pagos = $db->venta_pagos->select("tipopago, SUM(venta.total) AS valorpago")->where("venta.caja_id=? AND venta.dia=? AND venta.anulado_id=0", array($cajaId, $dia))->group("tipopago");
				if($cajeroId>0){
					$pagos->where("venta.cajero_id", $cajeroId);
				}

				$imprimir = new Imprimir($impresora, $cia);
				$response = $imprimir->cierre($comprobante, $pagos, $productos, array(
					"igv" => $igv,
					"servicio" => $servicio,
					"dia" => $dia,
					"cajero" => $cajero
				));
				/*if($response["data"]['success'] && $cajeroId==0){
					$newDay = $caja["dia"]+1;
					$caja->update(array("dia"=>$newDay));
				}*/

			}
		}
		$app->response()->write(json_encode($response));
	});

});

?>