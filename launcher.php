<?php 

$app->group('/launcher', function () use ($app, $db, $result) {

	$app->get('/:id', function($id) use ($app, $db, $result) {
		$modulos_id = $db->rol_modulo->where("rol_id", $id);
		foreach ($modulos_id as $mid) {
			$modulo = $db->modulo()->where('id', $mid['modulo_id']);
			array_push($result['data'], $modulo->fetch());
		}
	    $app->response()->write(json_encode($result));
	});

});


?>