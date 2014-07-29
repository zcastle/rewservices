<?php

$app->group('/ubigeo', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->ubigeo as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/lima', function() use ($app, $db, $result) {
		$rows = $db->ubigeo->where(array(
			"co_departamento" => "15",
			"co_provincia" => "01",
			"NOT co_distrito" => "01"
		));
		foreach ($rows as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>