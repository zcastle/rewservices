<?php

$app->group('/mesa', function () use ($app, $db, $result) {

	$app->get('/', function() use ($app, $db, $result) {
		foreach ($db->atenciones_c as $row) {
			$b = $db->atenciones()->where('nroatencion', $row['id'])->select('nroatencion')->group('nroatencion')[0];
			$row['estado'] = $b==null ? 'L' : 'O';
			array_push($result['data'], $row);
		}
	    $app->response()->write(json_encode($result));
	});

});

?>