<?php

$app->group('/tipo_operacion', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->tipo_operacion as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});
	
});
?>