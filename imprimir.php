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

	$app->get('/comprobante/:id', function($id) use ($app, $db) {

		$cabecera = $db->venta->where("id", $id);
		$detalle = $db->venta_detalle->where("venta_id", $id);

		$cia = null;
		$impresora = "LPT1"; //192.168.0.100
		$despedida = "";

		$venta = $cabecera->fetch();
		$caja = $db->caja->where("id", $venta["caja_id"]);
		$tipo = $venta['tipo_documento_id'];
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
			$impresora = $tipo==Imprimir::FACTURA ? $row['impresora_f'] : $row['impresora_b'];
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

		$imprimir = new Imprimir($impresora, $cia);
		$response = $imprimir->comprobante($cliente, $detalle, array(
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
			"servicio" => $servicio
		));

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
			$atencion = $db->atenciones->where("caja_id=? AND nroatencion=? AND enviado='S' AND producto.destino_id=?", array($cajaId, $nroAtencion, $row["id"]));
			if($atencion->fetch()){
				$imprimir = new Imprimir($row["destino"]);
				$response = $imprimir->pedido($atencion, $row["nombre"], array(
					"mozo" => $mozo
				));
				if($response["data"]['success']){
					foreach ($atencion as $row) {
						$row->update(array("enviado" => "S"));
					}
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
				$objComprobante = $db->venta->select("serie, numero")->where("caja_id=? AND dia=? AND tipo_documento_id=?", array($cajaId, $dia, $tipo))->order("numero");
				if($cajeroId>0){
					$objComprobante->where("cajero_id", $cajeroId);
				}
				$count = count($objComprobante);
				$first = $objComprobante->fetch()["numero"];
				$serie = $objComprobante->fetch()["serie"];
				$first = Util::right($serie, 3, "0")."-".Util::right($first, 7, "0");
				$last = 0;
				foreach ($objComprobante as $row) {
					$last = $row["numero"];
				}
				$last = Util::right($serie, 3, "0")."-".Util::right($last, 7, "0");
				$objComprobante = $db->venta->where("caja_id=? AND dia=? AND tipo_documento_id=? AND anulado_id>0", array($cajaId, $dia, $tipo));
				if($cajeroId>0){
					$objComprobante->where("cajero_id", $cajeroId);
				}
				$comprobante[$tipo] = array(
					"count" => $count,
					"first" => $first,
					"last" => $last,
					"anulado" => count($objComprobante)
				);
			}
			$productos = $db->venta_detalle->select("producto_name, precio, SUM(cantidad) AS cantidad")->where("venta.caja_id=? AND venta.dia=?", array($cajaId, $dia))->group("producto_name, precio");
			if($cajeroId>0){
				$ventas->where("cajero_id", $cajeroId);
			}

			$imprimir = new Imprimir($impresora, $cia);
			$response = $imprimir->cierre($comprobante, $productos, array(
				"igv" => $igv,
				"servicio" => $servicio,
				"dia" => $dia,
				"cajero" => $cajero
			));
			if($response["data"]['success'] && $cajeroId==0){
				
			}

		}
		$app->response()->write(json_encode($response));
	});

});

?>