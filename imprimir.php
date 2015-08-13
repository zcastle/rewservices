<?php

require_once('lib/imprimir.class.php');

$app->group('/imprimir', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		$almacen = $db->almacen;
		$app->response()->write( json_encode($almacen));
	});

	$app->get('/precuenta/:cajaId/:nroAtencion', function($cajaId, $nroAtencion) use ($app, $db, $result) {

		$atencion = $db->atenciones->where("caja_id=? AND nroatencion=?", array($cajaId, $nroAtencion));

		$cia = null;
		$impresoraPrecuenta = "LPT1"; //192.168.0.100
		$despedida = "";
		$caja = $db->caja->where("id", $cajaId);

		if($row = $caja->fetch()){
			$cia = $row->centrocosto->empresa;
			$despedida = $row->centrocosto["mensaje"];
			$impresoraPrecuenta = $row['impresora_p'];
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

		$imprimir = new Imprimir($impresoraPrecuenta, $cia);

		$response = $imprimir->precuenta($atencion, array(
			"cabecera" => $hasCabecera, 
			"cajero" => $cajero, 
			"mozo" => $mozo,
			"despedida" => $despedida
			));

		$app->response()->write(json_encode($response));
	});

	$app->get('/comprobante/:id', function($id) use ($app, $db, $result) {

		$cabecera = $db->venta->where("id", $id);
		$detalle = $db->venta_detalle->where("venta_id", $id);

		$cia = null;
		$impresoraPrecuenta = "LPT1"; //192.168.0.100
		$despedida = "";

		$venta = $cabecera->fetch();
		$caja = $db->caja->where("id", $venta["caja_id"]);
		$tipo = $venta['tipo_documento_id'];
		$serie = $venta['serie'];
		$numero = $venta['numero'];
		if($row = $caja->fetch()){
			$cia = $row->centrocosto->empresa;
			$registradora = $row['seriecaja'];
			$autorizacion = $row['autorizacion'];
			$impresoraPrecuenta = $tipo==Imprimir::FACTURA ? $row['impresora_f'] : $row['impresora_b'];
		}

		$objCajero = $db->usuario->where("id", $venta["cajero_id"])->fetch();
		$cajero = $objCajero["nombre"]." ".$objCajero["apellido"];

		$imprimir = new Imprimir($impresoraPrecuenta, $cia);

		$response = $imprimir->comprobante($cabecera, $detalle, array(
			"cajero" => $cajero,
			"registradora" => null,
			"autorizacion" => null,
			"factura" => $tipo==Imprimir::FACTURA ? true : false,
			"registradora" => $registradora,
			"autorizacion" => $autorizacion,
			"serie" => $serie,
			"numero" => $numero,
			"igv" => 18,
			"servicio" => 10
			));

		$app->response()->write(json_encode($response));
	});

});







?>