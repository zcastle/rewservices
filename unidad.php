<?php

$app->group('/unidad', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		$tb =  $db->unidad->where('visible', 'S');
		foreach ($tb as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

	$app->get('/producto/:id', function($id) use ($app, $db, $result) {
		$tb = $db->producto->where('id', $id);
		foreach ($tb as $row) {
			$unidad = $row->unidad;
			//array_push($result['data'], $unidad);
			array_push($result['data'], array(
				'id' => 'mayor',
				'nombre' => $unidad['mayor'],
				'cantidad' => 1
			));

			array_push($result['data'], array(
				'id' => 'menor',
				'nombre' => $unidad['menor'],
				'cantidad' => $unidad['cantidad']
			));
		}
	    $app->response()->write(json_encode($result));
	});

});

?>