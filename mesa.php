<?php

$app->group('/mesa', function () use ($app, $db, $result) {

	$app->get('/:caja_id', function($caja_id) use ($app, $db, $result) {
		$cc_id = $db->caja('id', $caja_id)->fetch()->centrocosto['id'];
		$caja_id = $db->caja('centrocosto_id', $cc_id)->and('tipo', 'C')->fetch()['id'];
		$c = $db->atenciones_c; //->limit(110);
		foreach ($c as $row) {
			$b = $db->atenciones()->where('nroatencion', $row['id'])->and('caja_id', $caja_id)->select('nroatencion')->group('nroatencion')[0];
			$row['estado'] = $b==null ? 'L' : 'O';
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>