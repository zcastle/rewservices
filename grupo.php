<?php

$app->group('/grupo', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->grupo as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>