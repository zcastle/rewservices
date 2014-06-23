<?php

$app->group('/cliente', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->cliente_proveedor as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/ruc/:ruc', function($ruc) use ($app, $db, $result) {
		$rows = $db->cliente_proveedor->where('ruc', $ruc);
		if ($row=$rows->fetch()) {
			array_push($result['data'], $row);
		} else {
			$result['success'] = false;
		}
	    $app->response()->write(json_encode($result));
	});
	
});

?>