<?php

require_once('lib/imprimir.class.php');

$app->group('/imprimir', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {

		$almacen = $db->almacen;

		$app->response()->write( json_encode($almacen));
	});

	$app->get('/precuenta/:atencion', function($atencion) use ($app, $db, $result) {

		$atencion = $db->atenciones->where('nroatencion', $atencion);

		$imprimir = new Imprimir("192.168.0.100");

		$imprimir->precuenta($atencion, null);



		$app->response()->write(json_encode($atencion));
	});

});







?>