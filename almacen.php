<?php

$app->group('/almacen', function () use ($app, $db, $result) {
	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->almacen as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});
});

?>