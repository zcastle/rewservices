<?php

$app->group('/corporacion', function () use ($app, $db, $result) {

	$app->get('/:caja_id', function($caja_id) use ($app, $db, $result) {
		$caja = $db->caja->where('id', $caja_id);
		foreach ($caja as $row) {
			array_push($result['data'], $row->centrocosto->empresa->corporacion);
		}
	    $app->response()->write(json_encode($result));
	});
});

?>