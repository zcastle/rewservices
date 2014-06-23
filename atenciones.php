<?php

$app->group('/atenciones', function () use ($app, $db, $result) { 
	$app->get('/:mesa', function($mesa) use ($app, $db, $result) {
		$rows = $db->atenciones->where('nroatencion', $mesa)->order('idatencion');
		foreach ($rows as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>