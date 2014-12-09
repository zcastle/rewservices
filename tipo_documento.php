<?php

$app->group('/tipo_documento', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->tipo_documento as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});
	
});
?>