<?php

$app->group('/corporacion', function () use ($app, $db, $result) {

	$app->get('/:id', function($id) use ($app, $db, $result) {
		$corporacion = $db->corporacion->where('id', $id);
		foreach ($corporacion as $row) {
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});
});

?>