<?php

$app->group('/ubigeo', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->ubigeo as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>